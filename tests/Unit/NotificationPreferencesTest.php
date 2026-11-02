<?php

use App\Enums\NotificationType;
use App\Http\Controllers\Website\ProfileController;
use App\Jobs\SendPushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Messaging;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'notification_preferences_test',
        'database.connections.notification_preferences_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        'redot.notifications.push_enabled' => false,
    ]);
    cache()->forever('settings.website_locales', ['ar', 'en']);
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->softDeletes();
    });
    Schema::create('user_preferences', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->unique();
        $table->string('theme')->default('light');
        $table->string('language')->default('ar');
        $table->timestamps();
    });
    $this->migration = require database_path('migrations/2026_11_01_000001_add_notification_preferences_to_user_preferences.php');
    $this->migration->up();
    (require database_path('migrations/0001_01_01_000015_create_notifications_table.php'))->up();
    (require database_path('migrations/2026_10_28_000001_create_push_subscriptions_table.php'))->up();

    DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    DB::table('user_preferences')->insert([['user_id' => 1], ['user_id' => 2]]);
    $this->user = User::findOrFail(1);
    $this->controller = new ProfileController;
});

afterEach(function () {
    $this->travelBack();
    DB::purge('notification_preferences_test');
});

function notificationPreferenceRequest(array $data): Request
{
    $request = Request::create('/', 'PUT', $data);
    $request->setUserResolver(fn () => test()->user);

    return $request;
}

test('notification type choices contain only the currently supported types and enable new types by default', function () {
    expect(array_keys(NotificationType::values()))->toBe(['general', 'new_offer', 'favorite_offer_expiring'])
        ->and(NotificationType::tryFrom('cashback'))->toBeNull()
        ->and($this->user->receivesWebsiteNotification(NotificationType::General))->toBeTrue()
        ->and($this->user->receivesWebsiteNotification(NotificationType::NewOffer))->toBeTrue();
});

test('notification preferences save only for the authenticated account and keep unrelated settings', function () {
    $response = $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 'on', 'notification_types' => ['general'],
        'user_id' => 2, 'country_id' => 999,
    ]));

    $preferences = $this->user->fresh()->preferences;
    expect($preferences->notifications_enabled)->toBeTrue()
        ->and($preferences->notification_types)->toBe(['disabled' => []])
        ->and($preferences->theme)->toBe('light')
        ->and($preferences->language)->toBe('ar')
        ->and(User::find(2)->preferences->notification_types)->toBeNull()
        ->and($response->getTargetUrl())->toBe(route('website.profile.edit', ['section' => 'notifications']));
});

test('turning notifications off retains the previously selected types for reactivation', function () {
    $this->user->preferences()->update(['notification_types' => ['general']]);
    $this->controller->updatePreferences(notificationPreferenceRequest(['notifications_enabled' => '0']));

    $user = $this->user->fresh();
    expect($user->preferences->notifications_enabled)->toBeFalse()
        ->and($user->preferences->notification_types)->toBe(['general'])
        ->and($user->receivesWebsiteNotification(NotificationType::General))->toBeFalse();
});

test('unsupported duplicate and invalid preferences cannot be saved', function (array $data, string $field) {
    try {
        $this->controller->updatePreferences(notificationPreferenceRequest($data));
        test()->fail('Expected validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field)
            ->and($this->user->fresh()->preferences->notification_types)->toBeNull();
    }
})->with([
    'unsupported type' => [['notifications_enabled' => 1, 'notification_types' => ['wallet']], 'notification_types.0'],
    'duplicate type' => [['notifications_enabled' => 1, 'notification_types' => ['general', 'general']], 'notification_types.0'],
    'invalid switch' => [['notifications_enabled' => 'invalid', 'notification_types' => ['general']], 'notifications_enabled'],
]);

test('ordinary preference updates preserve notification settings', function () {
    $this->user->preferences()->update(['notifications_enabled' => false, 'notification_types' => ['general']]);
    $this->controller->updatePreferences(notificationPreferenceRequest(['theme' => 'dark']));

    $preferences = $this->user->fresh()->preferences;
    expect($preferences->theme)->toBe('dark')
        ->and($preferences->notifications_enabled)->toBeFalse()
        ->and($preferences->notification_types)->toBe(['general']);
});

test('only explicitly unchecked rendered choices are disabled and old forms preserve newer choices', function () {
    $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 1,
        'notification_type_options' => array_keys(NotificationType::values()),
        'notification_types' => ['general', 'favorite_offer_expiring'],
    ]));
    $user = $this->user->fresh();
    expect($user->preferences->notification_types)->toBe(['disabled' => ['new_offer']])
        ->and($user->receivesWebsiteNotification(NotificationType::NewOffer))->toBeFalse()
        ->and($user->receivesWebsiteNotification(NotificationType::FavoriteOfferExpiring))->toBeTrue();

    $this->user = $user;
    $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 1, 'notification_types' => ['general'],
    ]));
    expect($this->user->fresh()->preferences->notification_types)->toBe(['disabled' => ['new_offer']]);
});

test('every visible choice can be unchecked without disabling future enum additions', function () {
    $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 1, 'notification_type_options' => ['general'],
    ]));
    $user = $this->user->fresh();
    expect($user->receivesWebsiteNotification(NotificationType::General))->toBeFalse()
        ->and($user->receivesWebsiteNotification(NotificationType::NewOffer))->toBeTrue()
        ->and($user->receivesWebsiteNotification(NotificationType::FavoriteOfferExpiring))->toBeTrue();

    $this->user = $user;
    $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 1, 'notification_type_options' => array_keys(NotificationType::values()),
    ]));
    expect($this->user->fresh()->preferences->enabledNotificationTypes())->toBe([]);
});

test('exclusion migration preserves legacy opt outs and master switch and supports rollback', function () {
    $this->user->preferences()->update(['notifications_enabled' => false, 'notification_types' => []]);
    $migration = require database_path('migrations/2026_11_02_000001_store_notification_type_exclusions.php');
    $migration->up();
    expect($this->user->fresh()->preferences->notification_types)->toBe(['disabled' => ['general']])
        ->and($this->user->fresh()->preferences->notifications_enabled)->toBeFalse()
        ->and(User::find(2)->preferences->notification_types)->toBe(['disabled' => []]);
    $migration->up();
    expect($this->user->fresh()->preferences->notification_types)->toBe(['disabled' => ['general']]);
    $migration->down();
    expect($this->user->fresh()->preferences->notification_types)->toBe([])
        ->and(User::find(2)->preferences->notification_types)->toBe(['general']);
});

test('inbox delivery respects the saved account settings', function (bool $enabled, ?array $types, int $expected) {
    $this->user->preferences()->update(['notifications_enabled' => $enabled, 'notification_types' => $types]);
    Notification::sendNow($this->user->fresh(), new WebsiteNotification('Title', body: 'Details'));

    expect(DB::table('notifications')->count())->toBe($expected);
})->with([
    'legacy account defaults' => [true, null, 1],
    'selected supported type' => [true, ['general'], 1],
    'master switch off' => [false, ['general'], 0],
    'type excluded' => [true, [], 0],
]);

test('browser delivery rechecks saved settings for personal and public notifications', function (bool $forBrowser, bool $enabled, array $types) {
    config(['redot.notifications.push_enabled' => true]);
    $this->user->preferences()->update(['notifications_enabled' => $enabled, 'notification_types' => $types]);
    $subscription = new PushSubscription(['token' => 'test-device-token']);
    $subscription->notifiable()->associate($this->user);
    $subscription->save();
    $recipient = $forBrowser ? $subscription : $this->user;
    $recipient->notifications()->create([
        'id' => 'a0a8e7ce-6043-4c15-822c-9ead3f8c38cf',
        'type' => WebsiteNotification::class,
        'data' => ['title' => 'Already stored', 'body' => 'Details'],
    ]);
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldNotReceive('send');
    app()->instance(Messaging::class, $messaging);

    (new SendPushNotification('a0a8e7ce-6043-4c15-822c-9ead3f8c38cf', $subscription->id, $subscription->token_hash, $forBrowser))->handle();
})->with([false, true])->with([[false, ['general']], [true, []]]);

test('public notification storage respects the signed in browser owners preference', function () {
    $this->user->preferences()->update(['notifications_enabled' => false]);
    $subscription = new PushSubscription(['token' => 'test-device-token']);
    $subscription->notifiable()->associate($this->user);
    $subscription->save();
    Notification::sendNow($subscription, new WebsiteNotification('Public message'));

    expect(DB::table('notifications')->count())->toBe(0);
});

test('one account preference update controls every registered browser without affecting another account', function () {
    config(['redot.notifications.push_enabled' => true]);
    $jobs = [];
    foreach ([1, 1, 2] as $index => $userId) {
        $user = User::findOrFail($userId);
        $subscription = new PushSubscription(['token' => 'browser-' . $index]);
        $subscription->notifiable()->associate($user);
        $subscription->save();
        $notification = $user->notifications()->create([
            'id' => 'a0a8e7ce-6043-4c15-822c-9ead3f8c38c' . $index,
            'type' => WebsiteNotification::class,
            'data' => ['title' => 'Account message', 'type' => NotificationType::General->value],
        ]);
        $jobs[] = new SendPushNotification($notification->id, $subscription->id, $subscription->token_hash);
    }
    $sentTokens = [];
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')->times(3)->andReturnUsing(function ($message) use (&$sentTokens) {
        $sentTokens[] = $message->jsonSerialize()['token'];

        return [];
    });
    app()->instance(Messaging::class, $messaging);

    $this->controller->updatePreferences(notificationPreferenceRequest(['notifications_enabled' => 0]));
    $jobs[0]->handle();
    $jobs[1]->handle();
    expect($sentTokens)->toBe([]);

    $jobs[2]->handle();
    expect($sentTokens)->toBe(['browser-2']);

    $this->controller->updatePreferences(notificationPreferenceRequest([
        'notifications_enabled' => 1, 'notification_types' => [NotificationType::General->value],
    ]));
    $jobs[0]->handle();
    $jobs[1]->handle();
    expect($sentTokens)->toBe(['browser-2', 'browser-0', 'browser-1']);
});

test('scheduled inbox delivery rechecks preferences changed while the notification was waiting', function () {
    $this->freezeTime();
    config([
        'queue.default' => 'notification_preferences_test',
        'queue.connections.notification_preferences_test' => [
            'driver' => 'database', 'connection' => 'notification_preferences_test', 'table' => 'jobs',
            'queue' => 'default', 'retry_after' => 90, 'after_commit' => false,
        ],
    ]);
    Schema::create('jobs', function (Blueprint $table) {
        $table->id();
        $table->string('queue');
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
    $sendAt = now()->addHour();
    Notification::send($this->user, (new WebsiteNotification('Scheduled message'))->delay($sendAt));
    expect(DB::table('jobs')->count())->toBe(1)
        ->and(Queue::connection()->pop())->toBeNull();

    $this->user->preferences()->update(['notifications_enabled' => false]);
    $this->travelTo($sendAt);
    while ($job = Queue::connection()->pop()) {
        $job->fire();
    }

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('notifications')->count())->toBe(0);
});

test('notification preference migration can be rolled back independently', function () {
    $this->migration->down();

    expect(Schema::hasColumn('user_preferences', 'notifications_enabled'))->toBeFalse()
        ->and(Schema::hasColumn('user_preferences', 'notification_types'))->toBeFalse()
        ->and(Schema::hasColumn('user_preferences', 'theme'))->toBeTrue();
});
