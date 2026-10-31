<?php

use App\Livewire\Notifications;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function () {
    $this->previousResolver = Model::getConnectionResolver();
    $connection = Mockery::mock(SQLiteConnection::class)->makePartial();
    $connection->__construct(null, ':memory:', '', ['driver' => 'sqlite']);
    $this->notificationRows = [];
    $this->notificationQueries = [];
    $connection->shouldReceive('select')->andReturnUsing(function ($sql) {
        $this->notificationQueries[] = $sql;

        return str_contains($sql, 'count(*)')
            ? [(object) ['aggregate' => $this->unreadCount]]
            : $this->notificationRows;
    });
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($connection);
    Model::setConnectionResolver($resolver);
    $this->unreadCount = 1;
});

afterEach(function () {
    Model::setConnectionResolver($this->previousResolver);
});

test('the website dropdown keeps only the accessible mark all action in its header', function () {
    Livewire::test(Notifications::class, ['website' => true])
        ->assertSee('ti-checks', false)
        ->assertSee('aria-label="' . __('Mark all as read') . '"', false)
        ->assertSee('wire:click="markAllAsRead"', false)
        ->assertSee(route('website.notifications.index'), false)
        ->assertSee('ti-arrow-left', false)
        ->assertDontSee('nav-segmented', false);
});

test('the website dropdown hides mark all when there are no unread notifications', function () {
    $this->unreadCount = 0;

    Livewire::test(Notifications::class, ['website' => true])
        ->assertDontSee('wire:click="markAllAsRead"', false)
        ->assertSee(__('No notifications yet'))
        ->assertSee(route('website.notifications.index'), false);
});

test('website rows show escaped truncated descriptions clocks and read state controls', function (bool $unread, string $locale) {
    app()->setLocale($locale);
    $this->notificationRows = [(object) [
        'id' => 'notification-id',
        'data' => json_encode(['title' => 'Sample notification', 'body' => '<script>alert(1)</script>']),
        'created_at' => '2026-10-31 12:00:00',
        'read_at' => $unread ? null : '2026-10-31 12:05:00',
    ]];
    $action = $unread ? 'markAsRead' : 'markAsUnread';
    $label = $unread ? __('Mark as read') : __('Mark as unread');

    Livewire::test(Notifications::class, ['website' => true])
        ->assertSee("wire:click=\"{$action}('notification-id')\"", false)
        ->assertSee('aria-label="' . $label . '"', false)
        ->assertSee('text-truncate', false)
        ->assertSee('ti-clock', false)
        ->assertDontSee('<time relative-time', false)
        ->assertSee('status-dot border border-primary ' . ($unread ? 'bg-primary' : 'bg-transparent'), false)
        ->assertSee('<button type="button" class="status-dot border border-primary', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
})->with([true, false])->with(['ar', 'en']);

test('website notification times keep the server wording across refreshes', function () {
    app()->setLocale('ar');
    $this->travelTo(now()->setTime(12, 5));
    $this->notificationRows = [(object) [
        'id' => 'notification-id',
        'data' => json_encode(['title' => 'Sample notification']),
        'created_at' => now()->subMinutes(5)->toDateTimeString(),
        'read_at' => null,
    ]];

    $component = Livewire::test(Notifications::class, ['website' => true])
        ->assertSee('منذ 5 دقائق')
        ->assertSee('wire:poll.15s.visible', false)
        ->assertDontSee('<time relative-time', false);

    $this->travel(1)->minutes();
    $component->call('$refresh')->assertSee('منذ 6 دقائق')->assertDontSee('قبل 6 دقائق');
});

test('the website dropdown requests the latest ten notifications', function () {
    Livewire::test(Notifications::class, ['website' => true]);

    expect(collect($this->notificationQueries)->contains(fn ($sql) => str_contains($sql, 'order by "created_at" desc limit 10')))->toBeTrue();
});
