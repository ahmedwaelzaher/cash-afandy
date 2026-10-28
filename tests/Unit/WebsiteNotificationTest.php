<?php

use App\Enums\NotificationLevel;
use App\Jobs\SendPushNotification;
use App\Models\Admin;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Bus;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;

uses(TestCase::class);

class AdditionalNotificationRecipient extends Model
{
    use Notifiable;
}

beforeEach(function () {
    $this->previousResolver = Model::getConnectionResolver();
    $this->previousMorphMap = Relation::morphMap();
    $this->connection = Mockery::mock(SQLiteConnection::class)->makePartial();
    $this->connection->__construct(null, ':memory:', '', ['driver' => 'sqlite']);
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($this->connection);
    Model::setConnectionResolver($resolver);
});

afterEach(function () {
    Model::setConnectionResolver($this->previousResolver);
    Relation::morphMap($this->previousMorphMap, false);
});

test('notifications retain the existing payload and wait for commit', function () {
    $notification = WebsiteNotification::make('Title', '/offers', NotificationLevel::Success, ['offer_id' => 42]);
    $notification->body = 'Body';

    expect($notification->afterCommit)->toBeTrue()
        ->and($notification->toArray(new User))->toBe([
            'title' => 'Title',
            'url' => '/offers',
            'level' => 'success',
            'lightbox' => false,
            'data' => ['offer_id' => 42],
            'body' => 'Body',
        ]);
});

test('database notifications accept any notifiable model', function (string $model) {
    expect((new WebsiteNotification('Title'))->via(new $model))->toBe(['database']);
})->with([User::class, Admin::class, AdditionalNotificationRecipient::class]);

test('push is disabled by default', function () {
    expect(config('redot.notifications.push_enabled'))->toBeFalse();
});

test('database-only delivery never queries push subscriptions', function (bool $enabled, bool $push, string $channel) {
    Bus::fake();
    config(['redot.notifications.push_enabled' => $enabled]);
    $this->connection->shouldNotReceive('select');

    (new WebsiteNotification('Title', push: $push))->afterSending(
        new AdditionalNotificationRecipient,
        $channel,
        new DatabaseNotification(['id' => 'notification-id']),
    );

    Bus::assertNothingDispatched();
})->with([
    'feature disabled' => [false, true, 'database'],
    'push disabled for notification' => [true, false, 'database'],
    'another channel' => [true, true, 'mail'],
]);

test('push requires a stored database notification', function () {
    Bus::fake();
    config(['redot.notifications.push_enabled' => true]);
    $this->connection->shouldNotReceive('select');

    (new WebsiteNotification('Title'))->afterSending(new User, 'database', null);

    Bus::assertNothingDispatched();
});

test('stored notifications queue active devices for any recipient type including morph aliases', function (string $model) {
    Bus::fake();
    config(['redot.notifications.push_enabled' => true]);
    Relation::morphMap(['recipient' => $model], false);
    $recipient = new $model;
    $recipient->id = 7;
    $this->connection->shouldReceive('select')->once()->andReturnUsing(function ($sql, $bindings) {
        expect($sql)->toContain('"notifiable_type" = ?', '"notifiable_id" = ?', '"disabled_at" is null')
            ->and($bindings)->toBe(['recipient', 7]);

        return array_map(fn ($id) => (object) [
            'id' => $id,
            'token_hash' => hash('sha256', 'device-' . $id),
        ], [11, 12]);
    });

    (new WebsiteNotification('Title'))->afterSending(
        $recipient,
        'database',
        new DatabaseNotification(['id' => 'notification-id', 'notifiable_type' => 'recipient', 'notifiable_id' => 7]),
    );

    Bus::assertDispatchedTimes(SendPushNotification::class, 2);
    foreach ([11, 12] as $id) {
        Bus::assertDispatched(SendPushNotification::class, fn ($job) => $job->notificationId === 'notification-id'
            && $job->subscriptionId === $id
            && $job->tokenHash === hash('sha256', 'device-' . $id)
            && $job->afterCommit === true);
    }
})->with([User::class, Admin::class, AdditionalNotificationRecipient::class]);

test('subscription tokens have unique fingerprints and ownership is not mass assignable', function () {
    $subscription = new PushSubscription([
        'token' => 'first-token',
        'notifiable_type' => User::class,
        'notifiable_id' => 99,
    ]);

    expect($subscription->token_hash)->toBe(hash('sha256', 'first-token'))
        ->and($subscription->toArray())->not->toHaveKeys(['token', 'token_hash', 'notifiable_type', 'notifiable_id']);

    $subscription->token = 'rotated-token';

    expect($subscription->token_hash)->toBe(hash('sha256', 'rotated-token'));
});

test('subscriptions can belong to any model through their polymorphic relation', function (string $model) {
    $recipient = new $model;
    $recipient->id = 7;
    $subscription = new PushSubscription;
    $subscription->notifiable()->associate($recipient);

    expect($subscription->notifiable)->toBe($recipient)
        ->and($subscription->notifiable_type)->toBe($recipient->getMorphClass())
        ->and($subscription->notifiable_id)->toBe(7);
})->with([User::class, Admin::class, AdditionalNotificationRecipient::class]);

test('disabled push jobs return before resolving Firebase or querying devices', function () {
    config(['redot.notifications.push_enabled' => false]);
    $this->connection->shouldNotReceive('select');

    (new SendPushNotification('notification-id', 11, 'token-hash'))->handle();

    expect(app()->resolved(Messaging::class))->toBeFalse();
});

test('push jobs retain retry metadata without serializing a device token', function () {
    $job = new SendPushNotification('notification-id', 11, hash('sha256', 'private-token'));

    expect($job->tries)->toBe(3)
        ->and($job->afterCommit)->toBeTrue()
        ->and($job->backoff())->toBe([30, 120, 300])
        ->and(serialize($job))->not->toContain('private-token');
});

test('push jobs ignore devices that are missing disabled or have a rotated token', function () {
    config(['redot.notifications.push_enabled' => true]);
    $this->connection->shouldReceive('select')->once()->andReturnUsing(function ($sql, $bindings) {
        expect($sql)->toContain('"id" = ?', '"token_hash" = ?', '"disabled_at" is null')
            ->and($bindings)->toBe([11, 'token-hash']);

        return [];
    });

    (new SendPushNotification('notification-id', 11, 'token-hash'))->handle();

    expect(app()->resolved(Messaging::class))->toBeFalse();
});

test('push jobs require a surviving recipient and a notification owned by the same type and id', function (string $model, bool $recipientExists) {
    config(['redot.notifications.push_enabled' => true]);
    Relation::morphMap(['recipient' => $model], false);
    $recipient = new $model;
    $this->connection->shouldReceive('select')->times($recipientExists ? 3 : 2)->andReturnUsing(function ($sql, $bindings) use ($recipient, $recipientExists) {
        if (str_contains($sql, 'from "push_subscriptions"')) {
            return [(object) ['id' => 11, 'notifiable_type' => 'recipient', 'notifiable_id' => 7, 'token' => 'device-token', 'token_hash' => 'token-hash']];
        }

        if (str_contains($sql, 'from "' . $recipient->getTable() . '"')) {
            expect($bindings)->toBe([7]);
            if ($recipient instanceof User) {
                expect($sql)->toContain('"users"."deleted_at" is null');
            }

            return $recipientExists ? [(object) ['id' => 7]] : [];
        }

        expect($sql)->toContain('from "notifications"', '"notifiable_type" = ?', '"notifiable_id" = ?', '"notifications"."id" = ?')
            ->and($bindings)->toBe(['recipient', 7, 'notification-id']);

        return [];
    });

    (new SendPushNotification('notification-id', 11, 'token-hash'))->handle();

    expect(app()->resolved(Messaging::class))->toBeFalse();
})->with([User::class, Admin::class, AdditionalNotificationRecipient::class])->with([false, true]);

test('Firebase delivery builds the SDK message and preserves subscriptions on unrelated failures', function (?string $failure) {
    config(['redot.notifications.push_enabled' => true]);
    $this->connection->shouldReceive('select')->times(3)->andReturn(
        [(object) ['id' => 11, 'notifiable_type' => AdditionalNotificationRecipient::class, 'notifiable_id' => 7, 'token' => 'device-token', 'token_hash' => 'token-hash']],
        [(object) ['id' => 7]],
        [(object) ['id' => 'notification-id', 'notifiable_type' => AdditionalNotificationRecipient::class, 'notifiable_id' => 7, 'data' => json_encode(['title' => 'Title', 'body' => 'Body'])]],
    );
    $messaging = Mockery::mock(Messaging::class);
    app()->instance(Messaging::class, $messaging);
    $send = $messaging->shouldReceive('send')->once()->with(Mockery::on(function (CloudMessage $message) {
        expect($message->jsonSerialize())->toMatchArray([
            'token' => 'device-token',
            'notification' => ['title' => 'Title', 'body' => 'Body'],
            'data' => ['notification_id' => 'notification-id'],
            'webpush' => ['notification' => ['tag' => 'notification-id']],
        ]);

        return true;
    }));

    if ($failure === null) {
        $send->andReturn(['name' => 'projects/test/messages/test']);
    } elseif ($failure === 'temporary') {
        $exception = new RuntimeException('Temporary connection failure');
        $send->andThrow($exception);
    } else {
        $exception = (new NotFound('Not found'))->withErrors([
            'error' => ['details' => [['errorCode' => $failure]]],
        ]);
        $send->andThrow($exception);
    }

    if ($failure === 'UNREGISTERED') {
        $this->connection->shouldReceive('update')->once()->andReturnUsing(function ($sql, $bindings) {
            expect($sql)->toContain('"notifiable_type" = ?', '"notifiable_id" = ?', '"token_hash" = ?')
                ->and($bindings)->toContain(11, AdditionalNotificationRecipient::class, 7, 'token-hash');

            return 1;
        });
    } else {
        $this->connection->shouldNotReceive('update');
    }

    $job = new SendPushNotification('notification-id', 11, 'token-hash');
    if ($failure !== null && $failure !== 'UNREGISTERED') {
        expect(fn () => $job->handle())->toThrow($exception);
    } else {
        $job->handle();
    }
})->with([null, 'temporary', 'NOT_FOUND', 'UNREGISTERED']);
