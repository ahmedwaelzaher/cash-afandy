<?php

use App\Actions\Programs\CompleteProgram;
use App\Actions\Programs\RecieveProgramReward;
use App\Actions\Programs\SubscribeToProgram;
use App\Enums\ProgramRewardStatus;
use App\Livewire\Datatables\ProgramRewards;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramSubscription;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\DashboardNotification;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Redot\Http\Middleware\EnsureDependenciesBuilt;
use Redot\Models\Language;
use Spatie\Permission\Models\Permission;

uses(TestCase::class);

beforeEach(function () {
    // Match the existing wallet tests and isolate financial behavior from unrelated migrations.
    config([
        'database.default' => 'program_test',
        'database.connections.program_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
    ]);
    foreach ([
        '0001_01_01_000000_create_admins_table.php',
        '0001_01_01_000005_create_permission_tables.php',
        '0001_01_01_000006_create_settings_table.php',
        '0001_01_01_000007_create_users_table.php',
        '0001_01_01_000010_create_languages_table.php',
        '0001_01_01_000012_create_countries_table.php',
        '0001_01_01_000012_create_states_table.php',
        '0001_01_01_000013_create_static_pages_table.php',
        '0001_01_01_000015_create_notifications_table.php',
        '2026_07_11_000012_create_user_preferences_table.php',
        '2026_10_03_000004_add_profile_fields_to_users_table.php',
        '2026_11_01_000001_add_notification_preferences_to_user_preferences.php',
        '2026_11_04_000001_create_wallets_and_wallet_transactions.php',
        '2026_11_05_000003_create_programs_and_subscriptions.php',
    ] as $migration) {
        (require database_path('migrations/' . $migration))->up();
    }

    // Supply only the countries and languages needed by real participation pages.
    foreach ([['eg', 'Egypt', 'مصر', 'EGP'], ['sa', 'Saudi Arabia', 'السعودية', 'SAR']] as [$code, $name, $native, $currency]) {
        Country::create([
            'code' => $code,
            'name' => $name,
            'native' => $native,
            'currency' => $currency,
            'phone' => '',
            'continent' => '',
            'capital' => '',
            'languages' => [],
        ]);
    }
    foreach (['ar', 'en'] as $locale) Language::create(['code' => $locale, 'name' => $locale, 'is_rtl' => $locale === 'ar']);
    cache()->forever('settings.website_locales', ['ar', 'en']);
    cache()->forever('settings.website_countries', ['eg', 'sa']);
    cache()->forever('settings.default_website_country', 'eg');
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('website-country-ip:127.0.0.1', 'eg');
    URL::defaults(['locale' => 'en']);
    Notification::fake();
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    $this->owner = User::factory()->completedProfile()->create();

    // Persist general definitions without inventing an enabled production program type.
    $this->createProgram = function (array $attributes = []): Program {
        $id = DB::table('programs')->insertGetId([
            'title' => json_encode(['ar' => 'برنامج', 'en' => 'Program']),
            'description' => json_encode(['ar' => 'الوصف', 'en' => 'Description']),
            'terms' => json_encode(['ar' => 'الشروط', 'en' => 'Terms']),
            'country_id' => $this->owner->country_id,
            'currency' => 'EGP',
            'type' => 'unconfigured',
            'reward_amount' => 2000,
            'repeatable' => false,
            'max_subscriptions' => 2,
            'requires_approval' => false,
            'active' => true,
            'launched_at' => now()->subDay(),
            'expired_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);

        return Program::findOrFail($id);
    };
    $this->createReward = function (ProgramSubscription $subscription, int $amount = 2000): ProgramReward {
        return $subscription->reward()->create([
            'amount' => $amount,
            'currency' => $subscription->program->currency,
            'status' => ProgramRewardStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    };
});

afterEach(function () {
    DB::purge('program_test');
});

test('program events notify active authorized administrators once', function (bool $manual) {
    // Limit dashboard notices to active administrators allowed to open their destination.
    $admin = Admin::factory()->create(['active' => true]);
    $inactive = Admin::factory()->create(['active' => false]);
    $unauthorized = Admin::factory()->create(['active' => true]);
    foreach (['dashboard.programs.show', 'dashboard.program-rewards.show'] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'admins']);
        $admin->givePermissionTo($permission);
        $inactive->givePermissionTo($permission);
    }

    // Duplicate enrollment reuses the subscription without sending a second notice.
    $program = ($this->createProgram)(['requires_approval' => $manual]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    app(SubscribeToProgram::class)->handle($this->owner, $program);
    Notification::assertSentTo($admin, DashboardNotification::class, fn ($notification) => $notification->url === route('dashboard.programs.show', $program)
        && $notification->data === ['program_subscription_id' => $subscription->id]);
    Notification::assertSentToTimes($admin, DashboardNotification::class, 1);

    // Manual rewards notify at review request, while automatic ones notify after payment.
    $reward = ($this->createReward)($subscription);
    Notification::assertSentToTimes($admin, DashboardNotification::class, $manual ? 2 : 1);
    app(RecieveProgramReward::class)->handle($reward, true, $manual ? $admin : null);
    app(RecieveProgramReward::class)->handle($reward, true, $manual ? $admin : null);
    Notification::assertSentToTimes($admin, DashboardNotification::class, 2);
    Notification::assertSentTo($admin, DashboardNotification::class, fn ($notification) => $notification->url === route('dashboard.program-rewards.show', $reward)
        && $notification->data === ['program_reward_id' => $reward->id]
        && $notification->level->value === ($manual ? 'warning' : 'success'));
    Notification::assertNotSentTo([$inactive, $unauthorized], DashboardNotification::class);
})->with([true, false]);

test('rolled back program events leave no dashboard notifications', function () {
    // Store real dashboard notices on the same connection as participation records.
    Notification::swap(new ChannelManager(app()));
    $admin = Admin::factory()->create(['active' => true]);
    foreach (['dashboard.programs.show', 'dashboard.program-rewards.show'] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'admins']);
        $admin->givePermissionTo($permission);
    }
    $program = ($this->createProgram)(['requires_approval' => true]);

    DB::beginTransaction();
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    ($this->createReward)($subscription);
    expect($admin->notifications()->count())->toBe(2);
    DB::rollBack();

    expect($admin->notifications()->count())->toBe(0)
        ->and($program->subscriptions()->count())->toBe(0);
});

test('administrators can open rewards filtered to a selected program', function () {
    // Give the administrator access to the existing reward listing.
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.program-rewards.index', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.program-rewards.index');
    $this->actingAs($admin, 'admins');

    // Keep rewards from another program out of the selected program's table.
    $program = ($this->createProgram)();
    $otherProgram = ($this->createProgram)();
    $reward = ($this->createReward)(app(SubscribeToProgram::class)->handle($this->owner, $program));
    ($this->createReward)(app(SubscribeToProgram::class)->handle($this->owner, $otherProgram));
    $datatable = new ProgramRewards;
    $datatable->programId = $program->id;

    expect($datatable->query()->pluck('id')->all())->toBe([$reward->id]);
    $this->get(route('dashboard.program-rewards.index', ['program_id' => $program->id]))
        ->assertOk()->assertViewHas('programId', $program->id);
    $this->get(route('dashboard.program-rewards.index'))
        ->assertOk()->assertViewHas('programId', null);
    $this->getJson(route('dashboard.program-rewards.index', ['program_id' => 'invalid']))
        ->assertUnprocessable();
});

test('repeatable programs have no expiration and remain available after time passes', function () {
    $program = ($this->createProgram)(['repeatable' => true, 'expired_at' => null]);
    $this->travel(100)->days();

    expect($program->fresh()->isAvailable())->toBeTrue()
        ->and(Program::available()->whereKey($program->id)->exists())->toBeTrue();
});

test('nonrepeatable programs expire exactly at their expiration time', function () {
    $program = ($this->createProgram)();
    $this->travelTo($program->expired_at);

    expect($program->isAvailable())->toBeFalse()
        ->and(Program::available()->whereKey($program->id)->exists())->toBeFalse();
});

test('duplicate enrollment returns the open attempt and payment permits an explicit new enrollment', function () {
    $program = ($this->createProgram)(['repeatable' => true, 'expired_at' => null]);
    $first = app(SubscribeToProgram::class)->handle($this->owner, $program);
    expect(app(SubscribeToProgram::class)->handle($this->owner, $program)->id)->toBe($first->id);
    $reward = ($this->createReward)($first);
    expect(app(SubscribeToProgram::class)->handle($this->owner, $program)->id)->toBe($first->id);
    app(RecieveProgramReward::class)->handle($reward, true);
    app(RecieveProgramReward::class)->handle($reward, true);
    expect($first->fresh()->completed_at)->not->toBeNull()
        ->and($program->subscriptions()->count())->toBe(1)
        ->and($this->owner->wallet->fresh()->balance)->toBe(2000);
    $second = app(SubscribeToProgram::class)->handle($this->owner, $program);
    expect($second->id)->not->toBe($first->id)
        ->and($second->completed_at)->toBeNull();
    app(RecieveProgramReward::class)->handle(($this->createReward)($second), true);

    expect(fn () => app(SubscribeToProgram::class)->handle($this->owner, $program))->toThrow(ValidationException::class);
    expect($program->subscriptions()->count())->toBe(2)
        ->and($this->owner->wallet->transactions()->count())->toBe(2);
});

test('the subscription limit applies to both dated and ongoing programs', function (bool $repeatable) {
    $program = ($this->createProgram)(['repeatable' => $repeatable, 'expired_at' => $repeatable ? null : now()->addDay(), 'max_subscriptions' => 1]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    app(RecieveProgramReward::class)->handle(($this->createReward)($subscription), true);

    expect(fn () => app(SubscribeToProgram::class)->handle($this->owner, $program))->toThrow(ValidationException::class);
})->with([false, true]);

test('pending and rejected rewards do not complete the subscription or permit a new attempt', function () {
    $program = ($this->createProgram)(['requires_approval' => true]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    $reward = ($this->createReward)($subscription);
    expect($subscription->fresh()->completed_at)->toBeNull();
    app(RecieveProgramReward::class)->handle($reward, false, Admin::factory()->create(), 'Not eligible');

    expect($subscription->fresh()->completed_at)->toBeNull()
        ->and(app(SubscribeToProgram::class)->handle($this->owner, $program)->id)->toBe($subscription->id)
        ->and($this->owner->wallet->fresh()->balance)->toBe(0);
});

test('payment failure rolls back completion and the reward decision', function () {
    $program = ($this->createProgram)();
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    $reward = ($this->createReward)($subscription);
    $this->owner->wallet->update(['balance' => Wallet::MAX_BALANCE]);
    expect(fn () => app(RecieveProgramReward::class)->handle($reward, true))->toThrow(ValidationException::class);

    expect($subscription->fresh()->completed_at)->toBeNull()
        ->and($reward->fresh()->status)->toBe(ProgramRewardStatus::Pending)
        ->and($this->owner->wallet->transactions()->count())->toBe(0);
});

test('earned rewards remain payable after expiry but expired programs cannot accept another attempt', function () {
    $program = ($this->createProgram)();
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    $reward = ($this->createReward)($subscription);
    $this->travel(2)->days();
    app(RecieveProgramReward::class)->handle($reward, true);

    expect($subscription->fresh()->completed_at)->not->toBeNull();
    expect(fn () => app(SubscribeToProgram::class)->handle($this->owner, $program))->toThrow(ValidationException::class);
});

test('another country cannot subscribe regardless of whether the program expires', function () {
    $program = ($this->createProgram)(['country_id' => Country::where('code', 'sa')->firstOrFail()->id, 'currency' => 'SAR']);

    expect(fn () => app(SubscribeToProgram::class)->handle($this->owner, $program))->toThrow(ValidationException::class);
});

test('sharing a currency never makes another countrys program eligible for enrollment', function () {
    $country = Country::where('code', 'sa')->firstOrFail();
    $country->update(['currency' => $this->owner->wallet->currency]);
    $program = ($this->createProgram)(['country_id' => $country->id, 'currency' => $country->currency]);

    expect($program->currency)->toBe($this->owner->wallet->currency);
    expect(fn () => app(SubscribeToProgram::class)->handle($this->owner, $program))->toThrow(ValidationException::class);
    $this->actingAs($this->owner, 'users')->postJson(route('website.programs.subscribe', $program), ['terms_accepted' => 1])->assertUnprocessable();

    expect($program->subscriptions()->count())->toBe(0);
});

test('the selected country supplies the program currency instead of submitted currency input', function () {
    $program = ($this->createProgram)();
    $country = Country::where('code', 'sa')->firstOrFail();
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.programs.edit', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.programs.edit');
    $this->actingAs($admin, 'admins')->put(route('dashboard.programs.update', $program), [
        'title' => ['ar' => 'برنامج', 'en' => 'Program'],
        'description' => ['ar' => 'الوصف', 'en' => 'Description'],
        'terms' => ['ar' => 'الشروط', 'en' => 'Terms'],
        'country_id' => $country->id,
        'currency' => 'USD',
        'active' => 'on',
    ])->assertRedirect();

    expect($program->fresh()->country_id)->toBe($country->id)
        ->and($program->fresh()->currency)->toBe($country->currency);
});

test('an existing subscription prevents changing program country even when currency remains the same', function () {
    $program = ($this->createProgram)();
    app(SubscribeToProgram::class)->handle($this->owner, $program);
    $country = Country::where('code', 'sa')->firstOrFail();
    $country->update(['currency' => $program->currency]);
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.programs.edit', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.programs.edit');
    $this->actingAs($admin, 'admins')->putJson(route('dashboard.programs.update', $program), [
        'title' => ['ar' => 'برنامج', 'en' => 'Program'],
        'description' => ['ar' => 'الوصف', 'en' => 'Description'],
        'terms' => ['ar' => 'الشروط', 'en' => 'Terms'],
        'country_id' => $country->id,
        'active' => 'on',
    ])->assertUnprocessable();

    expect($program->fresh()->country_id)->toBe($this->owner->country_id);
});

test('a completed subscriber can see the rejoin form and their separate reward history', function (string $locale) {
    $program = ($this->createProgram)(['repeatable' => true, 'expired_at' => null]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    app(RecieveProgramReward::class)->handle(($this->createReward)($subscription), true);
    $this->actingAs($this->owner, 'users')->get(route('website.programs.show', ['program' => $program, 'locale' => $locale]))
        ->assertOk()->assertSee('name="terms_accepted"', false);
    $this->post(route('website.programs.subscribe', $program), ['terms_accepted' => 1])->assertRedirect();
    $this->get(route('website.programs.show', ['program' => $program, 'locale' => $locale]))
        ->assertOk()->assertDontSee('name="terms_accepted"', false);

    expect($program->subscriptions()->count())->toBe(2);
})->with(['ar', 'en']);

test('ongoing program forms hide expiration using the existing visibility initializer', function () {
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.programs.create', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.programs.create');
    $this->actingAs($admin, 'admins')->get(route('dashboard.programs.create'))->assertOk()
        ->assertSee('visible-when="! $program-repeatable"', false)
        ->assertSee('name="max_subscriptions"', false)
        ->assertSee('name="type"', false);
});

test('content updates preserve ongoing dates and cannot change the agreed subscription limit', function () {
    $program = ($this->createProgram)(['repeatable' => true, 'expired_at' => null]);
    app(SubscribeToProgram::class)->handle($this->owner, $program);
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.programs.edit', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.programs.edit');
    $payload = [
        'title' => ['ar' => 'برنامج', 'en' => 'Program'],
        'description' => ['ar' => 'الوصف', 'en' => 'Description'],
        'terms' => ['ar' => 'الشروط', 'en' => 'Terms'],
        'active' => 'on',
    ];
    $this->actingAs($admin, 'admins')->put(route('dashboard.programs.update', $program), $payload)->assertRedirect();
    expect($program->fresh()->expired_at)->toBeNull()->and($program->fresh()->repeatable)->toBeTrue();
    $this->putJson(route('dashboard.programs.update', $program), [...$payload, 'max_subscriptions' => 3])->assertUnprocessable();
    expect($program->fresh()->max_subscriptions)->toBe(2);
});

test('repeated evaluation returns the existing reward without invoking another eligibility query', function () {
    $program = ($this->createProgram)();
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    $reward = ($this->createReward)($subscription);
    $evaluate = app(CompleteProgram::class);

    expect($evaluate->handle($subscription)->id)->toBe($reward->id);
    app(RecieveProgramReward::class)->handle($reward, true);
    expect($evaluate->handle($subscription)->id)->toBe($reward->id)
        ->and($this->owner->wallet->transactions()->count())->toBe(1);
});

test('joining snapshots the displayed reward without refreshing it on duplicate enrollment', function () {
    $program = ($this->createProgram)(['reward_amount' => 2500]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    expect($subscription->reward_amount)->toBe(2500);
    $program->update(['reward_amount' => 5000]);
    $existing = app(SubscribeToProgram::class)->handle($this->owner, $program);

    expect($existing->id)->toBe($subscription->id)->and($existing->reward_amount)->toBe(2500);
});

test('wallet payment uses the earned reward amount independently of the subscription display snapshot', function () {
    $program = ($this->createProgram)(['reward_amount' => 2500]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    app(RecieveProgramReward::class)->handle(($this->createReward)($subscription, 1750), true);

    expect($subscription->fresh()->reward_amount)->toBe(2500)
        ->and($subscription->reward->amount)->toBe(1750)
        ->and($this->owner->wallet->fresh()->balance)->toBe(1750);
});

test('administrators can update the display reward without changing existing subscription snapshots', function () {
    $program = ($this->createProgram)(['reward_amount' => 2500]);
    $subscription = app(SubscribeToProgram::class)->handle($this->owner, $program);
    $admin = Admin::factory()->create();
    Permission::create(['name' => 'dashboard.programs.edit', 'guard_name' => 'admins']);
    $admin->givePermissionTo('dashboard.programs.edit');
    $this->actingAs($admin, 'admins')->put(route('dashboard.programs.update', $program), [
        'title' => ['ar' => 'برنامج', 'en' => 'Program'],
        'description' => ['ar' => 'الوصف', 'en' => 'Description'],
        'terms' => ['ar' => 'الشروط', 'en' => 'Terms'],
        'reward_amount' => '50.00',
        'active' => 'on',
    ])->assertRedirect();

    expect($program->fresh()->reward_amount)->toBe(5000)
        ->and($subscription->fresh()->reward_amount)->toBe(2500);
});
