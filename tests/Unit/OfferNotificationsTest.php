<?php

use App\Jobs\SendOfferNotifications;
use App\Jobs\SendPushNotification;
use App\Models\Offer;
use App\Models\OfferNotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Contract\Messaging;

uses(TestCase::class);

beforeEach(function () {
    config(['database.default' => 'offer_notifications_test',
        'database.connections.offer_notifications_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        'redot.notifications.push_enabled' => false]);
    $this->travelTo(Carbon::parse('2026-11-02 12:00:00', config('app.timezone')));
    foreach (['default_website_country' => 'eg', 'website_countries' => ['eg', 'sa'],
        'show_website_countries_dropdown' => true, 'website_locales' => ['ar', 'en']] as $key => $value) {
        cache()->forever('settings.' . $key, $value);
    }
    Schema::create('countries', function (Blueprint $table) {
        $table->id(); $table->string('code'); $table->string('name');
    });
    Schema::create('stores', function (Blueprint $table) {
        $table->id(); $table->json('title'); $table->boolean('active')->default(true); $table->string('logo')->nullable();
    });
    Schema::create('users', function (Blueprint $table) {
        $table->id(); $table->softDeletes(); $table->timestamps();
    });
    Schema::create('user_preferences', function (Blueprint $table) {
        $table->id(); $table->unsignedBigInteger('user_id')->unique();
        $table->unsignedBigInteger('country_id')->nullable(); $table->string('language')->default('ar');
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_01_000001_add_notification_preferences_to_user_preferences.php'))->up();
    Schema::create('offers', function (Blueprint $table) {
        $table->id(); $table->unsignedBigInteger('store_id'); $table->json('title');
        $table->boolean('active')->default(true); $table->dateTime('launch_date');
        $table->dateTime('expiration_date')->nullable(); $table->timestamps();
    });
    Schema::create('country_offer', function (Blueprint $table) {
        $table->unsignedBigInteger('country_id'); $table->unsignedBigInteger('offer_id');
    });
    (require database_path('migrations/2026_10_28_000001_create_offer_user_table.php'))->up();
    (require database_path('migrations/0001_01_01_000015_create_notifications_table.php'))->up();
    (require database_path('migrations/2026_10_28_000001_create_push_subscriptions_table.php'))->up();
    DB::table('countries')->insert([['id' => 1, 'code' => 'eg', 'name' => 'Egypt'],
        ['id' => 2, 'code' => 'sa', 'name' => 'Saudi Arabia'], ['id' => 3, 'code' => 'xx', 'name' => 'Unsupported']]);
    DB::table('stores')->insert(['id' => 1, 'title' => json_encode(['ar' => 'متجر', 'en' => 'Store']), 'active' => true]);
    DB::table('users')->insert(array_map(fn ($id) => ['id' => $id, 'created_at' => now()->subDay()], range(1, 6)));
    DB::table('user_preferences')->insert([
        ['user_id' => 1, 'country_id' => 1, 'language' => 'ar', 'notifications_enabled' => true, 'notification_types' => null],
        ['user_id' => 2, 'country_id' => 2, 'language' => 'en', 'notifications_enabled' => true, 'notification_types' => null],
        ['user_id' => 3, 'country_id' => null, 'language' => 'en', 'notifications_enabled' => true, 'notification_types' => null],
        ['user_id' => 4, 'country_id' => 3, 'language' => 'ar', 'notifications_enabled' => true, 'notification_types' => null],
        ['user_id' => 5, 'country_id' => 1, 'language' => 'ar', 'notifications_enabled' => false, 'notification_types' => null],
        ['user_id' => 6, 'country_id' => 1, 'language' => 'ar', 'notifications_enabled' => true, 'notification_types' => json_encode(['disabled' => ['new_offer']])],
    ]);
    $this->eventMigration = require database_path('migrations/2026_11_02_000002_create_offer_notification_events_table.php');
    $this->eventMigration->up();
});

afterEach(function () {
    $this->travelBack();
    DB::purge('offer_notifications_test');
});

function automaticOffer(array $attributes = [], array $countries = [1]): Offer
{
    $offer = Offer::create([...[
        'store_id' => 1, 'title' => ['ar' => 'عرض جديد', 'en' => 'New deal'],
        'active' => true, 'launch_date' => now()->subMinute(), 'expiration_date' => now()->addDays(3),
    ], ...$attributes]);
    $offer->countries()->attach($countries);

    return $offer;
}

function storedAutomaticNotifications(?string $type = null): Collection
{
    return DB::table('notifications')->orderBy('notifiable_id')->get()->filter(fn ($row) => $type === null || json_decode($row->data, true)['type'] === $type)->values();
}

test('new publications respect country preferences opt outs localization and replay prevention', function () {
    $offer = automaticOffer();
    (new SendOfferNotifications)->handle();
    $rows = storedAutomaticNotifications('new_offer');
    expect($rows->pluck('notifiable_id')->all())->toBe([1, 3, 4])
        ->and(json_decode($rows[0]->data, true)['title'])->toContain('عرض جديد')
        ->and(json_decode($rows[1]->data, true)['title'])->toContain('New deal')
        ->and(json_decode($rows[1]->data, true)['url'])->toContain('/en/offers/' . $offer->id);
    $offer->update(['title' => ['ar' => 'عنوان معدل', 'en' => 'Updated title']]);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications()->count())->toBe(3);
});

test('inactive future expired and countryless offers are not announced', function (array $attributes, array $countries) {
    automaticOffer($attributes, $countries);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications()->count())->toBe(0);
})->with([
    'inactive' => [['active' => false], [1]],
    'future' => [['launch_date' => '2026-11-03 12:00:00'], [1]],
    'expired' => [['expiration_date' => '2026-11-02 11:59:00'], [1]],
    'no market' => [[], []],
]);

test('scheduled offers are announced when they actually launch', function () {
    $offer = automaticOffer(['launch_date' => now()->addHour()]);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications()->count())->toBe(0);
    $this->travelTo($offer->launch_date);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications()->count())->toBe(3);
});

test('existing published offers establish a baseline without a publication backlog', function () {
    $this->eventMigration->down();
    automaticOffer();
    $this->eventMigration->up();
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications()->count())->toBe(0)
        ->and(OfferNotificationEvent::where('type', 'new_offer')->count())->toBe(1);
});

test('favorites receive one reminder per expiry including favorites added inside the reminder window', function () {
    $offer = automaticOffer(['expiration_date' => now()->addHours(25)]);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->count())->toBe(0);
    $this->travel(1)->hours();
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->pluck('notifiable_id')->all())->toBe([1]);
    $offer->favoritedBy()->attach(3);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->pluck('notifiable_id')->all())->toBe([1, 3]);
    $offer->favoritedBy()->detach(1);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->count())->toBe(2);
});

test('moving an expiry changes the reminder window and indefinite offers have no reminders', function () {
    $offer = automaticOffer(['expiration_date' => now()->addHours(30)]);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    $offer->update(['expiration_date' => now()->addHours(50)]);
    $this->travel(7)->hours();
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->count())->toBe(0);
    $offer->update(['expiration_date' => null]);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring')->count())->toBe(0);
});

test('browser delivery rechecks the current offer favorite market and account settings', function (string $change) {
    $offer = automaticOffer(['expiration_date' => now()->addHours(12)]);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    $notification = storedAutomaticNotifications('favorite_offer_expiring')->first();
    $user = User::findOrFail(1);
    $subscription = new PushSubscription(['token' => 'device-token']);
    $subscription->notifiable()->associate($user);
    $subscription->save();
    match ($change) {
        'removed' => $offer->favoritedBy()->detach(1),
        'inactive' => $offer->update(['active' => false]),
        'extended' => $offer->update(['expiration_date' => now()->addDays(4)]),
        'country' => $user->preferences()->update(['country_id' => 2]),
        'optout' => $user->preferences()->update(['notification_types' => ['disabled' => ['favorite_offer_expiring']]]),
        'store' => DB::table('stores')->where('id', 1)->update(['active' => false]),
        'expired' => $this->travel(13)->hours(),
        'deleted' => $offer->delete(),
    };
    config(['redot.notifications.push_enabled' => true]);
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldNotReceive('send');
    app()->instance(Messaging::class, $messaging);
    (new SendPushNotification($notification->id, $subscription->id, $subscription->token_hash))->handle();
})->with(['removed', 'inactive', 'extended', 'country', 'optout', 'store', 'expired', 'deleted']);

test('recipient checkpoints commit atomically and let a retry resume without duplicate inbox rows', function () {
    for ($id = 7; $id <= 108; $id++) {
        DB::table('users')->insert(['id' => $id, 'created_at' => now()->subDay()]);
        DB::table('user_preferences')->insert(['user_id' => $id, 'country_id' => 1]);
    }
    automaticOffer();
    $sender = Notification::getFacadeRoot();
    $calls = 0;
    Notification::shouldReceive('sendNow')->andReturnUsing(function ($user, $notification) use (&$calls, $sender) {
        if (++$calls === 101) throw new RuntimeException('Delivery interrupted');
        $sender->sendNow($user, $notification);
    });
    expect(fn () => (new SendOfferNotifications)->handle())->toThrow(RuntimeException::class);
    $beforeRetry = DB::table('notifications')->count();
    expect($beforeRetry)->toBeGreaterThan(0)
        ->and(OfferNotificationEvent::first()->recipient_cursor)->toBeGreaterThan(0)
        ->and(OfferNotificationEvent::first()->processed_at)->toBeNull();
    Notification::swap($sender);
    (new SendOfferNotifications)->handle();
    expect(DB::table('notifications')->count())->toBe(105)
        ->and(DB::table('notifications')->distinct()->count('notifiable_id'))->toBe(105);
});

test('eligible automatic notifications reach Firebase with their stored contents', function () {
    DB::table('stores')->where('id', 1)->update(['logo' => 'https://example.test/store-logo.png']);
    $offer = automaticOffer(['expiration_date' => now()->addHours(12)]);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    $notification = storedAutomaticNotifications('favorite_offer_expiring')->first();
    $subscription = new PushSubscription(['token' => 'eligible-device']);
    $subscription->notifiable()->associate(User::findOrFail(1));
    $subscription->save();
    config(['redot.notifications.push_enabled' => true]);
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')->once()->andReturnUsing(function ($message) use ($notification) {
        $payload = $message->jsonSerialize();
        expect($payload['token'])->toBe('eligible-device')
            ->and($payload['notification']['title'])->toBe(json_decode($notification->data, true)['title'])
            ->and($payload['data']['url'])->toBe(json_decode($notification->data, true)['url'])
            ->and($payload['data']['notification_type'])->toBe('favorite_offer_expiring')
            ->and($payload['data']['store_image'])->toBe('https://example.test/store-logo.png');

        return [];
    });
    app()->instance(Messaging::class, $messaging);
    (new SendPushNotification($notification->id, $subscription->id, $subscription->token_hash))->handle();
});

test('explicitly excluded reminders do not enter the inbox', function () {
    User::findOrFail(1)->preferences()->update(['notification_types' => ['disabled' => ['favorite_offer_expiring']]]);
    $offer = automaticOffer(['expiration_date' => now()->addHours(12)]);
    $offer->favoritedBy()->attach(1);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications('favorite_offer_expiring'))->toHaveCount(0);
});

test('expired baseline offers are not reannounced when their expiration is extended', function () {
    $this->eventMigration->down();
    $offer = automaticOffer(['expiration_date' => now()->subDay()]);
    $this->eventMigration->up();
    $offer->update(['expiration_date' => now()->addDays(2)]);
    (new SendOfferNotifications)->handle();
    expect(storedAutomaticNotifications())->toHaveCount(0);
});
