<?php

use App\Actions\Wallets\PostWalletTransaction;
use App\Enums\NotificationType;
use App\Enums\WalletTransactionType;
use App\Livewire\Datatables\WalletTransactions;
use App\Models\Admin;
use App\Models\Country;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Redot\Http\Middleware\RoutePermission;
use Redot\Models\LoginToken;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'wallets_test',
        'database.connections.wallets_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
        'redot.notifications.push_enabled' => false,
    ]);
    foreach ([
        '0001_01_01_000000_create_admins_table.php',
        '0001_01_01_000003_create_personal_access_tokens_table.php',
        '0001_01_01_000005_create_permission_tables.php',
        '0001_01_01_000006_create_settings_table.php',
        '0001_01_01_000007_create_users_table.php',
        '0001_01_01_000010_create_languages_table.php',
        '0001_01_01_000012_create_countries_table.php',
        '0001_01_01_000012_create_states_table.php',
        '0001_01_01_000013_create_static_pages_table.php',
        '0001_01_01_000014_create_login_tokens_table.php',
        '0001_01_01_000015_create_notifications_table.php',
        '2026_07_11_000012_create_user_preferences_table.php',
        '2026_10_03_000003_add_social_ids_to_users_table.php',
        '2026_10_03_000004_add_profile_fields_to_users_table.php',
        '2026_10_28_000001_create_push_subscriptions_table.php',
        '2026_11_01_000001_add_notification_preferences_to_user_preferences.php',
        '2026_11_03_000001_add_referral_codes_and_referrals.php',
        '2026_11_04_000001_create_wallets_and_wallet_transactions.php',
    ] as $migration) {
        (require database_path('migrations/' . $migration))->up();
    }
    DB::table('languages')->insert([
        ['code' => 'en', 'name' => 'English', 'is_rtl' => false],
        ['code' => 'ar', 'name' => 'العربية', 'is_rtl' => true],
    ]);
    foreach (['index', 'show', 'create'] as $permission) {
        Permission::findOrCreate('dashboard.wallets.' . $permission, 'admins');
    }
    cache()->forever('website-country-ip:127.0.0.1', '');
    URL::defaults(['locale' => app()->getLocale()]);
    $this->notificationManager = Notification::getFacadeRoot();
    Notification::fake();
    $this->egypt = Country::create(['code' => 'eg', 'name' => 'Egypt', 'native' => 'مصر', 'phone' => '20', 'continent' => 'AF', 'capital' => 'Cairo', 'currency' => 'EGP', 'languages' => ['ar']]);
    $this->saudi = Country::create(['code' => 'sa', 'name' => 'Saudi Arabia', 'native' => 'السعودية', 'phone' => '966', 'continent' => 'AS', 'capital' => 'Riyadh', 'currency' => 'SAR', 'languages' => ['ar']]);
    $this->owner = User::factory()->verified()->create(['gender' => 'male']);
    $this->owner->initializeWallet($this->egypt->id);
    $this->admin = Admin::factory()->create();
    $this->admin->givePermissionTo(['dashboard.wallets.index', 'dashboard.wallets.show', 'dashboard.wallets.create']);
    $this->posting = [
        'type' => 'credit', 'amount' => '100.25', 'description' => 'Approved account adjustment',
        'idempotency_key' => (string) Str::uuid(),
    ];
});

afterEach(function () {
    DB::purge('wallets_test');
});

function postTestWalletEntry(User $user, Admin $admin, int $amount = 10025, WalletTransactionType $type = WalletTransactionType::Credit, ?string $key = null): WalletTransaction
{
    if (! $user->wallet) {
        $user->forceFill(['gender' => 'male'])->save();
        $user->initializeWallet(Country::code('eg')->id);
    }

    return app(PostWalletTransaction::class)->handle($user, $type, $amount, $admin, 'Approved account adjustment', $key ?? (string) Str::uuid(), $admin);
}

test('admin posts exact minor units with an authoritative source and actor', function (string $amount, int $minor) {
    $this->actingAs($this->admin, 'admins')->post(route('dashboard.wallets.store', $this->owner), [
        ...$this->posting, 'amount' => $amount, 'source_type' => User::class, 'source_id' => $this->owner->id,
        'admin_id' => 9999, 'balance_after' => 9999, 'currency' => 'USD',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard.wallets.show', $this->owner));

    $transaction = WalletTransaction::sole();
    expect($this->owner->fresh()->wallet->balance)->toBe($minor)
        ->and($this->owner->fresh()->wallet->currency)->toBe('EGP')
        ->and($transaction->amount)->toBe($minor)
        ->and($transaction->balance_after)->toBe($minor)
        ->and($transaction->source->is($this->admin))->toBeTrue()
        ->and($transaction->admin_id)->toBe($this->admin->id);
})->with([['0.01', 1], ['1.2', 120], ['100.25', 10025], ['9999999999.99', Wallet::MAX_BALANCE]]);

test('invalid monetary input cannot create a wallet or a transaction', function (string $amount) {
    $this->actingAs($this->admin, 'admins')->post(route('dashboard.wallets.store', $this->owner), [
        ...$this->posting, 'amount' => $amount,
    ])->assertSessionHasErrors('amount');

    expect(Wallet::count())->toBe(1)->and(WalletTransaction::count())->toBe(0);
    Notification::assertNothingSent();
})->with(['0', '-1', '1.001', '1e3', '1,000.00', '10000000000.00']);

test('debits preserve an auditable balance and cannot overdraw', function () {
    postTestWalletEntry($this->owner, $this->admin);
    $this->actingAs($this->admin, 'admins')->post(route('dashboard.wallets.store', $this->owner), [
        ...$this->posting, 'type' => 'debit', 'amount' => '20.10',
    ])->assertSessionHasNoErrors();
    expect($this->owner->fresh()->wallet->balance)->toBe(8015);

    $this->post(route('dashboard.wallets.store', $this->owner), [
        ...$this->posting, 'idempotency_key' => (string) Str::uuid(), 'type' => 'debit', 'amount' => '80.16',
    ])->assertSessionHasErrors('amount');
    expect($this->owner->fresh()->wallet->balance)->toBe(8015)->and(WalletTransaction::count())->toBe(2);
});

test('the same request is posted and notified only once while conflicting reuse is rejected', function () {
    $this->actingAs($this->admin, 'admins');
    $url = route('dashboard.wallets.store', $this->owner);
    $this->post($url, $this->posting)->assertSessionHasNoErrors();
    $this->post($url, $this->posting)->assertSessionHasNoErrors();
    $this->post($url, [...$this->posting, 'amount' => '200'])->assertSessionHasErrors('idempotency_key');

    expect(WalletTransaction::count())->toBe(1)->and($this->owner->fresh()->wallet->balance)->toBe(10025);
    Notification::assertSentToTimes($this->owner, WebsiteNotification::class, 1);
});

test('a wallet cannot exceed its supported balance limit', function () {
    postTestWalletEntry($this->owner, $this->admin, Wallet::MAX_BALANCE);
    expect(fn () => postTestWalletEntry($this->owner, $this->admin, 1))->toThrow(ValidationException::class);
    expect($this->owner->fresh()->wallet->balance)->toBe(Wallet::MAX_BALANCE)->and(WalletTransaction::count())->toBe(1);
});

test('failed ledger insertion rolls back the balance and sends no notification', function () {
    postTestWalletEntry($this->owner, $this->admin);
    Notification::fake();
    WalletTransaction::creating(function () {
        throw new RuntimeException('Ledger unavailable');
    });
    try {
        expect(fn () => postTestWalletEntry($this->owner, $this->admin, 200))->toThrow(RuntimeException::class);
    } finally {
        WalletTransaction::clearBootedModels();
        WalletTransaction::flushEventListeners();
    }

    expect($this->owner->fresh()->wallet->balance)->toBe(10025)->and(WalletTransaction::count())->toBe(1);
    Notification::assertNothingSent();
});

test('posted entries cannot be edited or deleted through the model', function () {
    $transaction = postTestWalletEntry($this->owner, $this->admin);
    expect(fn () => $transaction->update(['amount' => 1]))->toThrow(LogicException::class)
        ->and(fn () => $transaction->delete())->toThrow(LogicException::class);
    expect($transaction->fresh()->amount)->toBe(10025);
});

test('wallet currency and ownership cannot be changed after creation', function () {
    postTestWalletEntry($this->owner, $this->admin);
    $wallet = $this->owner->fresh()->wallet;
    expect(fn () => $wallet->update(['currency' => 'SAR']))->toThrow(ValidationException::class);
    expect($wallet->fresh()->currency)->toBe('EGP');

    $other = User::factory()->verified()->create();
    expect(fn () => $wallet->fresh()->update(['user_id' => $other->id]))->toThrow(ValidationException::class);
    expect($wallet->fresh()->user_id)->toBe($this->owner->id);
});

test('ledger equals the saved balance after multiple additions and deductions', function () {
    postTestWalletEntry($this->owner, $this->admin, 101);
    postTestWalletEntry($this->owner, $this->admin, 205);
    postTestWalletEntry($this->owner, $this->admin, 99, WalletTransactionType::Debit);
    $balance = WalletTransaction::all()->sum(fn ($entry) => $entry->type === WalletTransactionType::Credit ? $entry->amount : -$entry->amount);
    expect($balance)->toBe(207)->and($this->owner->fresh()->wallet->balance)->toBe($balance);
});

test('deleted owners retain readable financial history but cannot receive new adjustments', function () {
    postTestWalletEntry($this->owner, $this->admin);
    $this->owner->delete();
    $this->actingAs($this->admin, 'admins')->get(route('dashboard.wallets.show', $this->owner))->assertOk();
    $this->post(route('dashboard.wallets.store', $this->owner), $this->posting)->assertNotFound();
    expect(fn () => DB::table('users')->where('id', $this->owner->id)->delete())->toThrow(QueryException::class);
});

test('user sees only their own wallet regardless of supplied owner identifiers', function (string $locale) {
    app()->setLocale($locale);
    $other = User::factory()->verified()->create();
    $mine = postTestWalletEntry($this->owner, $this->admin);
    $theirs = postTestWalletEntry($other, $this->admin, 9900);
    $this->actingAs($this->owner, 'users')->get(route('website.wallet.show', [
        'locale' => $locale, 'user_id' => $other->id, 'wallet_id' => $other->wallet->id,
    ]))->assertOk()->assertSee($mine->reference)->assertDontSee($theirs->reference)->assertSee('100.25');
})->with(['ar', 'en']);

test('opening an empty wallet does not write financial data', function () {
    $this->actingAs($this->owner, 'users')->get(route('website.wallet.show'))->assertOk()->assertSee('0.00');
    expect(Wallet::count())->toBe(1);
});

test('guests and website users cannot post administrative adjustments', function () {
    $this->postJson(route('dashboard.wallets.store', $this->owner), $this->posting)->assertUnauthorized();
    $this->actingAs($this->owner, 'users')->postJson(route('dashboard.wallets.store', $this->owner), $this->posting)->assertUnauthorized();
    expect(WalletTransaction::count())->toBe(0);
});

test('wallet permissions distinguish viewing from posting and protect Livewire queries', function () {
    $this->withMiddleware(RoutePermission::class);
    $reader = Admin::factory()->create();
    $reader->givePermissionTo(['dashboard.wallets.index', 'dashboard.wallets.show']);
    $this->actingAs($reader, 'admins')->get(route('dashboard.wallets.index'))->assertOk();
    $this->get(route('dashboard.wallets.show', $this->owner))->assertOk()->assertDontSee('name="amount"', false);
    $this->post(route('dashboard.wallets.store', $this->owner), $this->posting)->assertForbidden();

    $outsider = Admin::factory()->create();
    $this->actingAs($outsider, 'admins')->get(route('dashboard.wallets.show', $this->owner))->assertForbidden();
    $table = new WalletTransactions;
    $table->userId = $this->owner->id;
    expect(fn () => $table->query())->toThrow(HttpException::class);
});

test('wallet notifications use the account type language and after commit delivery', function () {
    $this->owner->preferences()->update(['language' => 'ar']);
    $entry = postTestWalletEntry($this->owner, $this->admin);
    Notification::assertSentTo($this->owner, WebsiteNotification::class, function ($notification) use ($entry) {
        return $notification->type === NotificationType::Wallet
            && $notification->locale === 'ar'
            && $notification->afterCommit === true
            && $notification->data['wallet_transaction_id'] === $entry->id;
    });
});

test('real wallet notifications wait for the outer commit and disappear on rollback', function () {
    Notification::swap($this->notificationManager);
    DB::beginTransaction();
    postTestWalletEntry($this->owner, $this->admin);
    expect(DB::table('notifications')->count())->toBe(0);
    DB::rollBack();
    expect(DB::table('notifications')->count())->toBe(0)->and($this->owner->fresh()->wallet->balance)->toBe(0);

    postTestWalletEntry($this->owner, $this->admin);
    expect(DB::table('notifications')->count())->toBe(1);
});

test('wallet preference and global switch suppress notifications without preventing postings', function (bool $enabled, array $types) {
    $this->owner->preferences()->update(['notifications_enabled' => $enabled, 'notification_types' => $types]);
    postTestWalletEntry($this->owner, $this->admin);
    expect($this->owner->fresh()->wallet->balance)->toBe(10025);
    Notification::assertNothingSent();
})->with([[true, ['disabled' => ['wallet']]], [false, ['disabled' => []]]]);

test('registration creates the wallet in the confirmed country currency', function (bool $api) {
    cache()->forever('settings.cloudflare_turnstile_site_key', '');
    $response = $this->post($api ? route('api.website.register.store') : route('website.register.store'), [
        'first_name' => 'New', 'last_name' => 'Member', 'email' => 'new@example.test',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        'gender' => 'female', 'country_id' => $this->saudi->id, 'wallet_currency' => 'USD',
    ]);
    $api ? $response->assertCreated() : $response->assertSessionHasNoErrors()->assertRedirect(route('website.index'));
    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->country_id)->toBe($this->saudi->id)->and($user->wallet->currency)->toBe('SAR')
        ->and($user->wallet->balance)->toBe(0)->and($user->hasCompletedProfile())->toBeTrue();
})->with([false, true]);

test('registration requires a supported account country and rolls back invalid registrations', function (?int $countryId) {
    cache()->forever('settings.cloudflare_turnstile_site_key', '');
    $this->post(route('website.register.store'), [
        'first_name' => 'New', 'last_name' => 'Member', 'email' => 'invalid-country@example.test',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        'gender' => 'male', 'country_id' => $countryId,
    ])->assertSessionHasErrors('country_id');
    expect(User::where('email', 'invalid-country@example.test')->exists())->toBeFalse()->and(Wallet::count())->toBe(1);
})->with([null, 999]);

test('incomplete users are redirected without trapping completion logout or public policies', function () {
    $user = User::factory()->verified()->create();
    $destination = route('website.wallet.show');
    $this->actingAs($user, 'users')->get($destination)
        ->assertRedirect(route('website.profile.complete'))->assertSessionHas('url.intended', $destination);
    $this->get(route('website.profile.complete'))->assertOk()->assertSee('name="country_id"', false);
    $this->getJson($destination)->assertStatus(409)->assertJsonPath('redirect', route('website.profile.complete'));
    $this->post(route('website.logout'))->assertRedirect(route('website.index'));
    $this->assertGuest('users');
});

test('profile completion preserves the intended destination and does not require optional details', function () {
    $user = User::factory()->verified()->create();
    $destination = route('website.wallet.show') . '#history';
    $this->actingAs($user, 'users')->withSession(['url.intended' => $destination])
        ->put(route('website.profile.complete.store'), [
            'first_name' => 'Confirmed', 'last_name' => 'Member', 'gender' => 'female', 'country_id' => $this->saudi->id,
        ])->assertSessionHasNoErrors()->assertRedirect($destination)->assertSessionMissing('url.intended');
    $user->refresh();
    expect($user->hasCompletedProfile())->toBeTrue()->and($user->wallet->currency)->toBe('SAR')
        ->and($user->birthdate)->toBeNull()->and($user->phone)->toBeNull();
    $this->get(route('website.wallet.show'))->assertOk()->assertSee('SAR');
});

test('completion cannot change an existing account country or wallet currency', function () {
    $this->actingAs($this->owner, 'users')->put(route('website.profile.complete.store'), [
        'first_name' => 'Unwanted', 'last_name' => 'Change', 'gender' => 'female', 'country_id' => $this->saudi->id,
    ])->assertSessionHasErrors('country_id');
    expect($this->owner->fresh()->country_id)->toBe($this->egypt->id)
        ->and($this->owner->fresh()->first_name)->not->toBe('Unwanted')
        ->and($this->owner->fresh()->wallet->currency)->toBe('EGP');
    expect(fn () => $this->owner->forceFill(['country_id' => $this->saudi->id])->save())->toThrow(ValidationException::class);
    expect($this->owner->fresh()->country_id)->toBe($this->egypt->id);
});

test('legacy wallets retain their currency when the account country is confirmed', function () {
    $user = User::factory()->verified()->create();
    $user->wallet()->create(['currency' => 'EGP', 'balance' => 0]);
    expect(fn () => $user->initializeWallet($this->saudi->id))->toThrow(ValidationException::class);
    $wallet = $user->initializeWallet($this->egypt->id);
    expect($wallet->currency)->toBe('EGP')->and($user->country_id)->toBe($this->egypt->id)
        ->and($user->wallet()->count())->toBe(1);
});

test('country confirmation rejects disabled markets while existing wallets continue working', function () {
    cache()->forever('settings.website_countries', ['sa']);
    $user = User::factory()->verified()->create();
    expect(fn () => $user->initializeWallet($this->egypt->id))->toThrow(ValidationException::class);
    postTestWalletEntry($this->owner, $this->admin, 50);
    expect($this->owner->fresh()->wallet->balance)->toBe(50);
});

test('administrative postings cannot choose a currency before the owner confirms the country', function () {
    $user = User::factory()->verified()->create();
    $this->actingAs($this->admin, 'admins')->post(route('dashboard.wallets.store', $user), $this->posting)
        ->assertSessionHasErrors('amount');
    expect($user->fresh()->wallet)->toBeNull();
});

test('changing the shopping market leaves the account country and wallet unchanged', function () {
    $this->actingAs($this->owner, 'users')->get(route('website.wallet.show', ['country' => 'sa']))->assertOk();
    expect($this->owner->fresh()->preferences->country_id)->toBe($this->saudi->id)
        ->and($this->owner->fresh()->country_id)->toBe($this->egypt->id)
        ->and($this->owner->fresh()->wallet->currency)->toBe('EGP');
});

test('social registration redirects to completion and preserves the originating page', function (string $provider) {
    cache()->forever('settings.' . $provider . '_client_id', 'test');
    cache()->forever('settings.' . $provider . '_client_secret', 'test');
    $driver = Mockery::mock();
    if ($provider === 'facebook') $driver->shouldReceive('fields')->andReturnSelf();
    $driver->shouldReceive('user')->once()->andReturn((new SocialiteUser)->setRaw([])->map([
        'id' => 'new-social-id', 'email' => 'social@example.test', 'name' => 'Social Member',
    ]));
    Socialite::shouldReceive('driver')->with($provider)->once()->andReturn($driver);
    $destination = route('website.wallet.show');
    $this->withSession(['url.intended' => $destination])->get(route('website.socialite.callback', ['provider' => $provider]))
        ->assertRedirect(route('website.profile.complete'))->assertSessionHas('url.intended', $destination);
    $user = auth('users')->user();
    expect($user->wallet)->toBeNull();
    $this->put(route('website.profile.complete.store'), [
        'first_name' => $user->first_name, 'last_name' => $user->last_name,
        'gender' => 'male', 'country_id' => $this->saudi->id,
    ])->assertRedirect($destination);
    expect($user->fresh()->wallet->currency)->toBe('SAR');
})->with(['google', 'facebook']);

test('regular and modal login send incomplete accounts to completion without consuming the destination', function (bool $modal) {
    $user = User::factory()->verified()->create();
    $destination = route('website.wallet.show');
    $request = $this->withSession(['url.intended' => $destination]);
    $credentials = ['email' => $user->email, 'password' => 'password'];
    if ($modal) {
        $request->withHeader('X-Login-Modal', 'true')->postJson(route('website.login.store'), $credentials)
            ->assertOk()->assertJsonPath('redirect', route('website.profile.complete'))->assertSessionHas('url.intended', $destination);
    } else {
        $request->post(route('website.login.store'), $credentials)->assertRedirect(route('website.profile.complete'))
            ->assertSessionHas('url.intended', $destination);
    }
})->with([false, true]);

test('magic links and email codes also require profile completion', function (bool $code) {
    $user = User::factory()->verified()->create();
    $token = LoginToken::generate($user->email, 'users');
    $destination = route('website.wallet.show');
    $this->withSession(['url.intended' => $destination]);
    $response = $code
        ? $this->post(route('website.magic-link-code.store'), ['email' => $user->email, 'code' => $token->code])
        : $this->get(route('website.magic-link.verify', ['token' => $token->token]));
    $response->assertRedirect(route('website.profile.complete'))->assertSessionHas('url.intended', $destination);
    $this->assertAuthenticatedAs($user, 'users');
})->with([false, true]);

test('wallet notifications and administrative forms use the actual wallet currency', function () {
    $user = User::factory()->verified()->create(['gender' => 'female']);
    $user->initializeWallet($this->saudi->id);
    $entry = postTestWalletEntry($user, $this->admin);
    Notification::assertSentTo($user, WebsiteNotification::class, fn ($notification) => str_contains($notification->body, 'SAR'));
    $this->actingAs($this->admin, 'admins')->get(route('dashboard.wallets.show', $user))->assertOk()->assertSee('SAR');
    expect($entry->wallet->currency)->toBe('SAR');
});

test('registration and completion share the country field currency preview and permanent choice hint', function (string $locale) {
    app()->setLocale($locale);
    $this->get(route('website.register', ['locale' => $locale]))->assertOk()
        ->assertSee('id="account-country"', false)->assertSee('visible-when="$account-country', false)
        ->assertSee(__('Choose the country where you live and receive your earnings. Your account country and wallet currency cannot be changed after confirmation.'));
    $user = User::factory()->verified()->create();
    $this->actingAs($user, 'users')->get(route('website.profile.complete', ['locale' => $locale]))->assertOk()
        ->assertSee('id="account-country"', false)->assertDontSee('name="birthdate"', false)->assertDontSee('name="phone"', false);
})->with(['ar', 'en']);
