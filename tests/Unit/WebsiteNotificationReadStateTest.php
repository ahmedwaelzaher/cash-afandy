<?php

use App\Livewire\Notifications;
use App\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->previousResolver = Model::getConnectionResolver();
    $this->connection = Mockery::mock(SQLiteConnection::class)->makePartial();
    $this->connection->__construct(null, ':memory:', '', ['driver' => 'sqlite']);
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($this->connection);
    Model::setConnectionResolver($resolver);
});

afterEach(function () {
    Model::setConnectionResolver($this->previousResolver);
});

test('website read state actions resolve ownership before saving the new state', function (string $action) {
    $user = new User;
    $user->id = 7;
    $this->actingAs($user, 'users');
    $this->connection->shouldReceive('select')->once()->andReturnUsing(function ($sql, $bindings) use ($user, $action) {
        expect($sql)->toContain('"notifiable_type" = ?', '"notifiable_id" = ?', '"notifications"."id" = ?')
            ->and($bindings)->toBe([$user->getMorphClass(), 7, 'own-notification']);

        return [(object) [
            'id' => 'own-notification',
            'read_at' => $action === 'markAsUnread' ? '2026-10-31 12:00:00' : null,
        ]];
    });
    $this->connection->shouldReceive('update')->once()->andReturnUsing(function ($sql, $bindings) use ($action) {
        expect($sql)->toContain('update "notifications" set "read_at" = ?', 'where "id" = ?')
            ->and(end($bindings))->toBe('own-notification');

        if ($action === 'markAsUnread') {
            expect($bindings[0])->toBeNull();
        } else {
            expect($bindings[0])->not->toBeNull();
        }

        return 1;
    });

    $component = new Notifications;
    $component->website = true;
    $component->{$action}('own-notification');
})->with(['markAsRead', 'markAsUnread']);

test('website read state actions reject notifications outside the current account', function (string $action) {
    $user = new User;
    $user->id = 7;
    $this->actingAs($user, 'users');
    $this->connection->shouldReceive('select')->once()->andReturnUsing(function ($sql, $bindings) use ($user) {
        expect($sql)->toContain('"notifiable_type" = ?', '"notifiable_id" = ?')
            ->and($bindings)->toBe([$user->getMorphClass(), 7, 'foreign-notification']);

        return [];
    });
    $this->connection->shouldNotReceive('update');
    $component = new Notifications;
    $component->website = true;

    expect(fn () => $component->{$action}('foreign-notification'))->toThrow(ModelNotFoundException::class);
})->with(['markAsRead', 'markAsUnread']);
