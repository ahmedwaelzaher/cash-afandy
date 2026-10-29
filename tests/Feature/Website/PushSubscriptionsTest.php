<?php

use App\Jobs\SendPushNotification;
use App\Models\Admin;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

trait SupportsPushSubscriptionSqliteTests
{
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') === 'sqlite') {
            // The project's existing generated columns use MySQL CONCAT.
            DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$values) => implode('', $values), -1, PDO::SQLITE_DETERMINISTIC);
        }
    }
}

uses(SupportsPushSubscriptionSqliteTests::class);

beforeEach(function () {
    config(['redot.notifications.push_enabled' => true]);
});

test('guests can register without assigning a recipient and disable their own browser', function () {
    $this->postJson(route('website.push-subscriptions.store'), [
        'token' => 'guest-device', 'notifiable_type' => User::class, 'notifiable_id' => 1,
    ])->assertNoContent();
    $subscription = PushSubscription::firstOrFail();
    expect($subscription->notifiable_type)->toBeNull()->and($subscription->notifiable_id)->toBeNull();

    $this->deleteJson(route('website.push-subscriptions.destroy'))->assertNoContent();
    expect($subscription->fresh()->disabled_at)->not->toBeNull();
});

test('a browser subscription belongs to the authenticated recipient and is registered idempotently', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user, 'users');

    foreach ([1, 2] as $attempt) {
        $this->postJson(route('website.push-subscriptions.store'), [
            'token' => 'device-token', 'notifiable_id' => $other->id, 'notifiable_type' => Admin::class,
        ])->assertNoContent()->assertSessionHas('push_subscription_token_hash', hash('sha256', 'device-token'));
    }

    $this->assertDatabaseCount('push_subscriptions', 1);
    $subscription = PushSubscription::firstOrFail();
    expect($subscription->notifiable->is($user))->toBeTrue()
        ->and($subscription->last_seen_at)->not->toBeNull()
        ->and($subscription->disabled_at)->toBeNull();
});

test('a rotated token disables the old subscription from this session only', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users');
    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'old-token'])->assertNoContent();
    $otherDevice = new PushSubscription(['token' => 'another-device']);
    $otherDevice->notifiable()->associate($user);
    $otherDevice->save();

    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'rotated-token'])->assertNoContent();

    expect(PushSubscription::where('token_hash', hash('sha256', 'old-token'))->firstOrFail()->disabled_at)->not->toBeNull()
        ->and($otherDevice->fresh()->disabled_at)->toBeNull();
    $this->assertDatabaseHas('push_subscriptions', ['token_hash' => hash('sha256', 'rotated-token'), 'disabled_at' => null]);
});

test('a shared browser transfers ownership when its authenticated account changes', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $this->actingAs($first, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'shared-browser'])->assertNoContent();
    $this->actingAs($second, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'shared-browser'])->assertNoContent();

    $this->assertDatabaseCount('push_subscriptions', 1);
    expect(PushSubscription::firstOrFail()->notifiable->is($second))->toBeTrue();

    PushSubscription::disableFor($first, hash('sha256', 'shared-browser'));
    expect(PushSubscription::firstOrFail()->disabled_at)->toBeNull();
});

test('disabling browser notifications affects only the current recipient and session', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'this-browser'])->assertNoContent();
    $other = new PushSubscription(['token' => 'other-browser']);
    $other->notifiable()->associate($user);
    $other->save();

    // Opt-out must remain available when the global sending feature is switched off.
    config(['redot.notifications.push_enabled' => false]);
    $this->deleteJson(route('website.push-subscriptions.destroy'), ['token' => 'other-browser'])
        ->assertNoContent()->assertSessionMissing('push_subscription_token_hash');

    expect(PushSubscription::where('token_hash', hash('sha256', 'this-browser'))->firstOrFail()->disabled_at)->not->toBeNull()
        ->and($other->fresh()->disabled_at)->toBeNull();
});

test('a browser can opt in again after disabling notifications', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'device-token'])->assertNoContent();
    $this->deleteJson(route('website.push-subscriptions.destroy'))->assertNoContent();
    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'device-token'])->assertNoContent();

    $this->assertDatabaseCount('push_subscriptions', 1);
    expect(PushSubscription::firstOrFail()->disabled_at)->toBeNull();
});

test('website logout detaches this browser without disabling other devices', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'this-browser'])->assertNoContent();
    $other = new PushSubscription(['token' => 'other-browser']);
    $other->notifiable()->associate($user);
    $other->save();

    $this->post(route('website.logout'))->assertRedirect(route('website.index'));

    $subscription = PushSubscription::where('token_hash', hash('sha256', 'this-browser'))->firstOrFail();
    expect($subscription->disabled_at)->toBeNull()->and($subscription->notifiable_type)->toBeNull()
        ->and($subscription->notifiable_id)->toBeNull()->and($other->fresh()->notifiable->is($user))->toBeTrue();
});

test('recipient types with matching numeric ids cannot disable each others subscriptions', function () {
    $admin = Admin::factory()->create();
    $user = User::factory()->create(['id' => $admin->id]);
    $subscription = new PushSubscription(['token' => 'admin-device']);
    $subscription->notifiable()->associate($admin);
    $subscription->save();

    $this->actingAs($user, 'users')->withSession(['push_subscription_token_hash' => $subscription->token_hash])
        ->deleteJson(route('website.push-subscriptions.destroy'))->assertNoContent();

    expect($subscription->fresh()->disabled_at)->toBeNull();
});

test('invalid browser tokens are rejected', function ($token) {
    $this->actingAs(User::factory()->create(), 'users')
        ->postJson(route('website.push-subscriptions.store'), ['token' => $token])
        ->assertUnprocessable()->assertJsonValidationErrors('token', 'payload');

    $this->assertDatabaseCount('push_subscriptions', 0);
})->with([null, '', str_repeat('a', 4097)]);

test('registration is unavailable when push is disabled', function () {
    config(['redot.notifications.push_enabled' => false]);

    $this->actingAs(User::factory()->create(), 'users')
        ->postJson(route('website.push-subscriptions.store'), ['token' => 'device-token'])->assertNotFound();
});

test('the queued notification is stored before its separate Firebase delivery job', function () {
    Queue::fake();
    $user = User::factory()->create();
    $subscription = new PushSubscription(['token' => 'queued-device']);
    $subscription->notifiable()->associate($user);
    $subscription->save();
    $user->notify(new WebsiteNotification('Queued title', body: 'Queued body'));

    $databaseJob = null;
    Queue::assertPushed(SendQueuedNotifications::class, function ($job) use (&$databaseJob) {
        $databaseJob = $job;

        return $job->notification instanceof WebsiteNotification;
    });
    $this->assertDatabaseCount('notifications', 0);
    $databaseJob->handle(app(ChannelManager::class));
    $this->assertDatabaseCount('notifications', 1);
    $notification = $user->notifications()->firstOrFail();
    $pushJob = null;
    Queue::assertPushed(SendPushNotification::class, function ($job) use (&$pushJob, $notification) {
        $pushJob = $job;

        return $job->notificationId === $notification->id;
    });

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')->once()->with(Mockery::on(fn (CloudMessage $message) => $message->jsonSerialize()['data']['notification_id'] === $notification->id))
        ->andReturn(['name' => 'projects/test/messages/test']);
    app()->instance(Messaging::class, $messaging);
    $pushJob->handle();
    $this->assertDatabaseCount('notifications', 1);
});

test('the website retains its push worker when asset caching is disabled', function () {
    config([
        'redot.notifications.vapid_key' => 'public-vapid-key',
        'redot.notifications.firebase' => ['apiKey' => 'public-api-key', 'projectId' => 'test', 'messagingSenderId' => '123', 'appId' => 'web-app'],
    ]);

    $this->actingAs(User::factory()->create(), 'users')->get(route('website.profile.edit'))
        ->assertOk()->assertSee('data-browser-notifications', false)
        ->assertSee('window.WebsitePush', false)
        ->assertSee('/service-worker.js?cache=0', false)
        ->assertDontSee('registration.unregister()', false)
        ->assertDontSee('service-account.json', false);
});

test('the browser invitation is available to guests and website users when push is enabled', function () {
    $this->get(route('website.index'))->assertOk()->assertSee('data-push-prompt hidden', false)
        ->assertSee('window.WebsitePush', false);

    $this->actingAs(User::factory()->create(), 'users')->get(route('website.index'))
        ->assertOk()->assertSee('data-push-prompt hidden', false)
        ->assertSee('data-push-dismiss', false)->assertSee('data-push-enable', false);

    config(['redot.notifications.push_enabled' => false]);
    $this->get(route('website.index'))->assertOk()->assertDontSee('data-push-prompt hidden', false);
});

test('guest token rotation and opt out do not affect another browser or account', function () {
    $user = User::factory()->create();
    $other = new PushSubscription(['token' => 'account-device']);
    $other->notifiable()->associate($user);
    $other->save();
    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'guest-old'])->assertNoContent();
    $previous = PushSubscription::where('token_hash', hash('sha256', 'guest-old'))->firstOrFail();
    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'guest-new'])->assertNoContent();
    expect($previous->fresh()->disabled_at)->not->toBeNull();

    $this->withSession(['push_subscription_token_hash' => $other->token_hash])
        ->deleteJson(route('website.push-subscriptions.destroy'))->assertNoContent();
    expect($other->fresh()->disabled_at)->toBeNull()->and($other->notifiable->is($user))->toBeTrue();
});

test('logging in attaches the existing guest browser without a second subscription', function () {
    $this->postJson(route('website.push-subscriptions.store'), ['token' => 'guest-login'])->assertNoContent();
    $user = User::factory()->create();
    $this->post(route('website.login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($user, 'users');
    $this->assertDatabaseCount('push_subscriptions', 1);
    expect(PushSubscription::firstOrFail()->notifiable->is($user))->toBeTrue();
});

test('public notifications reach a guest through the existing notification and push jobs', function () {
    Queue::fake();
    $subscription = PushSubscription::create(['token' => 'public-guest']);
    $subscription->notify(new WebsiteNotification('Coupon', body: 'Public offer'));
    $databaseJob = null;
    Queue::assertPushed(SendQueuedNotifications::class, function ($job) use (&$databaseJob) {
        $databaseJob = $job;

        return true;
    });
    $databaseJob->handle(app(ChannelManager::class));
    $stored = $subscription->notifications()->firstOrFail();
    $pushJob = null;
    Queue::assertPushed(SendPushNotification::class, function ($job) use (&$pushJob, $subscription, $stored) {
        $pushJob = $job;

        return $job->forBrowser && $job->subscriptionId === $subscription->id && $job->notificationId === $stored->id;
    });
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')->once()->with(Mockery::on(fn (CloudMessage $message) => $message->jsonSerialize()['token'] === 'public-guest'))
        ->andReturn(['name' => 'projects/test/messages/public']);
    app()->instance(Messaging::class, $messaging);
    $pushJob->handle();
});

test('logging out blocks pending personal delivery while preserving public browser delivery', function () {
    Queue::fake();
    $user = User::factory()->create();
    $this->actingAs($user, 'users')->postJson(route('website.push-subscriptions.store'), ['token' => 'logout-browser'])->assertNoContent();
    $subscription = PushSubscription::firstOrFail();
    $private = $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => WebsiteNotification::class, 'data' => ['title' => 'Private']]);
    $public = $subscription->notifications()->create(['id' => (string) Str::uuid(), 'type' => WebsiteNotification::class, 'data' => ['title' => 'Public']]);
    $this->post(route('website.logout'))->assertRedirect();

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')->once()->with(Mockery::on(fn (CloudMessage $message) => $message->jsonSerialize()['notification']['title'] === 'Public'))
        ->andReturn(['name' => 'projects/test/messages/public']);
    app()->instance(Messaging::class, $messaging);
    (new SendPushNotification($private->id, $subscription->id, $subscription->token_hash))->handle();
    (new SendPushNotification($private->id, $subscription->id, $subscription->token_hash, forBrowser: true))->handle();
    (new SendPushNotification($public->id, $subscription->id, $subscription->token_hash, forBrowser: true))->handle();
});
