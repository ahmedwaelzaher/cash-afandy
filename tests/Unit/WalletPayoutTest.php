<?php

use App\Enums\WalletPayoutMethod;
use App\Http\Controllers\Website\WalletController;
use App\Http\Middleware\EnsureProfileIsComplete;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPayout;
use App\Notifications\DashboardNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Redot\Http\Middleware\RoutePermission;
use Spatie\Permission\Models\Permission;

uses(TestCase::class);

beforeEach(function () {
    // Keep financial tests isolated from the project's configured database.
    config([
        'database.default' => 'wallet_payout_test',
        'database.connections.wallet_payout_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
    ]);
    cache()->forever('settings.website_locales', ['ar', 'en']);
    cache()->forever('settings.website_countries', ['eg', 'sa']);
    cache()->forever('settings.default_website_country', 'eg');
    cache()->forever('settings.show_website_countries_dropdown', false);
    Notification::fake();
    URL::defaults(['locale' => 'en']);

    // Use the real wallet migrations against a minimal account schema.
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('full_name');
        $table->string('email');
        $table->softDeletes();
        $table->timestamps();
    });
    Schema::create('admins', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('active')->default(true);
        $table->softDeletes();
    });
    Schema::create('user_preferences', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('language');
        $table->boolean('notifications_enabled')->default(true);
        $table->json('notification_types')->nullable();
    });
    Schema::create('countries', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->string('native');
        $table->string('currency');
    });
    foreach ([
        '0001_01_01_000005_create_permission_tables.php',
        '0001_01_01_000015_create_notifications_table.php',
        '2026_11_04_000001_create_wallets_and_wallet_transactions.php',
        '2026_11_05_000002_create_wallet_payouts_table.php',
    ] as $migration) {
        (require database_path('migrations/' . $migration))->up();
    }

    DB::table('users')->insert([
        ['id' => 1, 'first_name' => 'Test', 'last_name' => 'Owner', 'full_name' => 'Test Owner', 'email' => 'owner@example.test'],
        ['id' => 2, 'first_name' => 'Other', 'last_name' => 'Owner', 'full_name' => 'Other Owner', 'email' => 'other@example.test'],
    ]);
    DB::table('admins')->insert(['id' => 1, 'name' => 'Reviewer']);
    DB::table('user_preferences')->insert(['user_id' => 1, 'language' => 'en']);
    DB::table('countries')->insert(['id' => 1, 'code' => 'eg', 'name' => 'Egypt', 'native' => 'مصر', 'currency' => 'EGP']);
    $this->user = User::findOrFail(1);
    $this->admin = Admin::findOrFail(1);
    $this->wallet = Wallet::create(['user_id' => 1, 'currency' => 'EGP', 'balance' => 10000]);

    Permission::create(['name' => 'dashboard.wallet-payouts.show', 'guard_name' => 'admins']);
    $this->admin->givePermissionTo('dashboard.wallet-payouts.show');

    // Exercise payout events without inventing an enabled withdrawal method.
    $this->createPayout = function (): WalletPayout {
        $payout = new WalletPayout([
            'wallet_id' => $this->wallet->id,
            'reference' => (string) Str::uuid(),
            'amount' => 2500,
            'details' => ['destination' => 'private-destination'],
            'status' => 'pending',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $payout->setRawAttributes([...$payout->getAttributes(), 'method' => 'unconfigured']);
        $payout->save();

        return $payout;
    };
});

afterEach(function () {
    DB::purge('wallet_payout_test');
});

test('new payout requests notify active authorized administrators once without exposing destinations', function () {
    DB::table('admins')->insert([
        ['id' => 2, 'name' => 'Inactive', 'active' => false],
        ['id' => 3, 'name' => 'Unauthorized', 'active' => true],
    ]);
    $inactive = Admin::findOrFail(2);
    $inactive->givePermissionTo('dashboard.wallet-payouts.show');
    $payout = ($this->createPayout)();
    $payout->update(['review_note' => 'Reviewing']);

    Notification::assertSentToTimes($this->admin, DashboardNotification::class, 1);
    Notification::assertSentTo($this->admin, DashboardNotification::class, fn ($notification) => $notification->url === route('dashboard.wallet-payouts.show', $payout)
        && $notification->data === ['wallet_payout_id' => $payout->id]
        && ! str_contains(json_encode($notification->toArray($this->admin)), 'private-destination'));
    Notification::assertNotSentTo([$inactive, Admin::findOrFail(3)], DashboardNotification::class);
});

test('rolled back payout requests leave no dashboard notification', function () {
    Notification::swap(new ChannelManager(app()));

    DB::beginTransaction();
    ($this->createPayout)();
    expect($this->admin->notifications()->count())->toBe(1);
    DB::rollBack();

    expect(WalletPayout::count())->toBe(0)
        ->and($this->admin->notifications()->count())->toBe(0);
});

test('payout requests reject methods until they are explicitly added to the enum', function (string $method) {
    $request = Request::create('/', 'POST', [
        'method' => $method,
        'amount' => '25.00',
        'payout_confirmation' => '1',
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $request->setUserResolver(fn () => $this->user);

    try {
        (new WalletController)->storePayout($request);
        $this->fail('An unconfigured payout method was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('method');
    }

    expect(WalletPayoutMethod::cases())->toBe([])
        ->and(WalletPayout::count())->toBe(0)
        ->and($this->wallet->transactions()->count())->toBe(0)
        ->and($this->wallet->fresh()->balance)->toBe(10000);
})->with(['electronic_wallet', 'bank_transfer', 'unknown']);

test('wallet page renders available balance and no payout form when no methods exist', function (string $locale) {
    app()->setLocale($locale);
    $source = file_get_contents(resource_path('views/website/wallet/show.blade.php'));
    $source = str_replace(['<x-layouts::website :title="__(\'Balance\')">', '</x-layouts::website>'], ['<div>', '</div>'], $source);
    $html = Blade::render($source, [
        'wallet' => $this->wallet,
        'payoutMethods' => WalletPayoutMethod::cases(),
        'transactions' => $this->wallet->transactions()->paginate(10),
        'errors' => (new ViewErrorBag)->put('default', new MessageBag),
    ]);

    $text = preg_replace('/\s+/u', ' ', strip_tags($html));

    expect($text)->toContain('100.00 EGP', __('Coming soon'));
    expect($html)->toContain('wallet-payout-title')
        ->not->toContain(__('Pending balance'), 'name="method"', 'name="amount"', 'name="currency"', 'name="details[phone]"');
})->with(['ar', 'en']);

test('transaction pagination preserves filters and never includes another wallet', function () {
    $other = Wallet::create(['user_id' => 2, 'currency' => 'EGP', 'balance' => 10000]);
    foreach (range(0, 11) as $index) {
        DB::table('wallet_transactions')->insert([
            'reference' => (string) Str::uuid(),
            'wallet_id' => $index === 11 ? $other->id : $this->wallet->id,
            'type' => 'credit',
            'amount' => 100,
            'balance_after' => 10000,
            'source_category' => 'admin_adjustment',
            'source_type' => Admin::class,
            'source_id' => 1,
            'admin_id' => 1,
            'description' => 'Test adjustment',
            'idempotency_key' => (string) Str::uuid(),
            'created_at' => now(),
        ]);
    }
    $request = Request::create('/en/wallet?type=credit&page=2');
    $this->app->instance('request', $request);
    $request->setUserResolver(fn () => $this->user);
    $data = (new WalletController)->show($request)->getData();

    expect($data['transactions']->total())->toBe(11)
        ->and($data['transactions']->count())->toBe(1)
        ->and($data['transactions']->first()->wallet_id)->toBe($this->wallet->id)
        ->and($data['transactions']->url(1))->toContain('type=credit');
});

test('payout destination details remain encrypted and hidden from serialization', function () {
    $payout = new WalletPayout(['details' => ['destination' => 'private-destination']]);

    expect($payout->details)->toBe(['destination' => 'private-destination'])
        ->and($payout->getAttributes()['details'])->not->toContain('private-destination')
        ->and($payout->toArray())->not->toHaveKey('details');
});

test('payout routes retain account authentication and dashboard permission middleware', function () {
    $router = app(Router::class);
    foreach (['index', 'show', 'update'] as $action) {
        $route = $router->getRoutes()->getByName('dashboard.wallet-payouts.' . $action);
        $middleware = $router->gatherRouteMiddleware($route);
        expect($middleware)->toContain('Illuminate\Auth\Middleware\Authenticate:admins', RoutePermission::class);
    }
    $route = $router->getRoutes()->getByName('website.wallet.payouts.store');
    expect($router->gatherRouteMiddleware($route))->toContain('Illuminate\Auth\Middleware\Authenticate:users', EnsureProfileIsComplete::class);
});
