<?php

use App\Livewire\Notifications;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;
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
            ? [(object) ['aggregate' => str_contains($sql, '"read_at" is null') ? $this->unreadCount : $this->totalCount]]
            : $this->notificationRows;
    });
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($connection);
    Model::setConnectionResolver($resolver);
    $this->unreadCount = 1;
    $this->totalCount = 12;
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
        ->assertDontSee('ti-settings', false)
        ->assertDontSee('nav-segmented', false);
});

test('the full website inbox retains filters and the labeled mark all action without settings', function () {
    Livewire::test(Notifications::class, ['website' => true, 'fullPage' => true])
        ->assertDontSee('ti-settings', false)
        ->assertSee('nav-segmented', false)
        ->assertSee('wire:click="markAllAsRead"', false)
        ->assertSee(__('Mark all as read'))
        ->assertSee('ti-checks', false);
});

test('website inbox filter counts stay independent and changing filters resets pagination', function () {
    app()->setLocale('ar');
    $this->totalCount = 31;
    $this->unreadCount = 3;

    Livewire::test(Notifications::class, ['website' => true, 'fullPage' => true])
        ->assertViewHas('totalCount', 31)
        ->assertViewHas('unreadCount', 3)
        ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 31)
        ->assertSee(__('pagination.showing'))
        ->assertDontSee('Showing')
        ->assertSee('wire:click.prevent="gotoPage(2, \'notificationsPage\')"', false)
        ->call('gotoPage', 2, 'notificationsPage')
        ->assertSet('paginators.notificationsPage', 2)
        ->assertSee('wire:click.prevent="previousPage(\'notificationsPage\')"', false)
        ->set('status', 'unread')
        ->assertSet('paginators.notificationsPage', 1)
        ->assertViewHas('totalCount', 31)
        ->assertViewHas('unreadCount', 3)
        ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 3);
});

test('shared pagination keeps ordinary navigation outside Livewire', function () {
    $paginator = new LengthAwarePaginator(collect(range(1, 15)), 31, 15, 1, ['path' => '/stores']);

    $this->view('components.pagination', [
        'paginator' => $paginator,
        'elements' => [1 => $paginator->getUrlRange(1, 3)],
    ])->assertSee('/stores?page=2', false)->assertDontSee('wire:click', false);
});

test('the website notification settings route is not registered', function () {
    expect(app('router')->has('website.notifications.settings'))->toBeFalse();
});

test('website notifications hide mark all when there are no unread notifications', function (bool $fullPage) {
    $this->unreadCount = 0;

    Livewire::test(Notifications::class, ['website' => true, 'fullPage' => $fullPage])
        ->assertDontSee('wire:click="markAllAsRead"', false)
        ->assertSee(__('No notifications yet'));
})->with([false, true]);

test('website rows show escaped truncated descriptions clocks and read state controls', function (bool $unread, string $locale, bool $fullPage) {
    app()->setLocale($locale);
    $this->notificationRows = [(object) [
        'id' => 'notification-id',
        'data' => json_encode(['title' => 'Sample notification', 'body' => '<script>alert(1)</script>']),
        'created_at' => '2026-10-31 12:00:00',
        'read_at' => $unread ? null : '2026-10-31 12:05:00',
    ]];
    $action = $unread ? 'markAsRead' : 'markAsUnread';
    $label = $unread ? __('Mark as read') : __('Mark as unread');

    Livewire::test(Notifications::class, ['website' => true, 'fullPage' => $fullPage])
        ->assertSee("wire:click=\"{$action}('notification-id')\"", false)
        ->assertSee('aria-label="' . $label . '"', false)
        ->assertSee('text-truncate', false)
        ->assertSee('ti-clock', false)
        ->assertDontSee('<time relative-time', false)
        ->assertSee('status-dot border border-primary ' . ($unread ? 'bg-primary' : 'bg-transparent'), false)
        ->assertSee('<button type="button" class="status-dot border border-primary', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
})->with([true, false])->with(['ar', 'en'])->with([false, true]);

test('website notification times keep the server wording across refreshes', function (bool $fullPage) {
    app()->setLocale('ar');
    $this->travelTo(now()->setTime(12, 5));
    $this->notificationRows = [(object) [
        'id' => 'notification-id',
        'data' => json_encode(['title' => 'Sample notification']),
        'created_at' => now()->subMinutes(5)->toDateTimeString(),
        'read_at' => null,
    ]];

    $component = Livewire::test(Notifications::class, ['website' => true, 'fullPage' => $fullPage])
        ->assertSee('منذ 5 دقائق')
        ->assertSee('wire:poll.15s.visible', false)
        ->assertDontSee('<time relative-time', false);

    $this->travel(1)->minutes();
    $component->call('$refresh')->assertSee('منذ 6 دقائق')->assertDontSee('قبل 6 دقائق');
})->with([false, true]);

test('the website dropdown requests the latest ten notifications', function () {
    Livewire::test(Notifications::class, ['website' => true]);

    expect(collect($this->notificationQueries)->contains(fn ($sql) => str_contains($sql, 'order by "created_at" desc limit 10')))->toBeTrue();
});
