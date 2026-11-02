<?php

use App\Http\Controllers\Dashboard\WebsiteNotificationController;
use App\Jobs\SendScheduledWebsiteNotifications;
use App\Livewire\Datatables\ScheduledWebsiteNotifications;
use App\Models\Admin;
use App\Models\ScheduledWebsiteNotification;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Redot\Http\Middleware\RoutePermission;
use Redot\Support\PermissionNameResolver;
use Redot\Support\QueryFilters;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'notification_test',
        'database.connections.notification_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        'queue.default' => 'notification_test',
        'queue.connections.notification_test' => [
            'driver' => 'database', 'connection' => 'notification_test', 'table' => 'jobs',
            'queue' => 'default', 'retry_after' => 90, 'after_commit' => false,
        ],
        'redot.notifications.push_enabled' => false,
    ]);
    Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00', config('app.timezone')));

    // Isolated in-memory tables avoid the unrelated legacy migration's MySQL CONCAT.
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('full_name');
        $table->string('email');
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamp('last_login_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
    Schema::create('jobs', function (Blueprint $table) {
        $table->id();
        $table->string('queue');
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
    Schema::create('user_preferences', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->unique();
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_01_000001_add_notification_preferences_to_user_preferences.php'))->up();
    (require database_path('migrations/2026_11_01_000002_create_scheduled_website_notifications_table.php'))->up();
    Schema::create('notifications', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
    DB::table('users')->insert([
        ['id' => 1, 'first_name' => 'Ahmed', 'last_name' => 'One', 'full_name' => 'Ahmed One', 'email' => 'one@example.test', 'deleted_at' => null],
        ['id' => 2, 'first_name' => 'Mona', 'last_name' => 'Two', 'full_name' => 'Mona Two', 'email' => 'two@example.test', 'deleted_at' => null],
        ['id' => 3, 'first_name' => 'Ahmed', 'last_name' => 'Deleted', 'full_name' => 'Ahmed Deleted', 'email' => 'deleted@example.test', 'deleted_at' => now()],
    ]);
    $this->composer = new WebsiteNotificationController;
    $this->payload = [
        'users' => [1, 2], 'title' => 'New offer', 'body' => '<b>Plain text details</b>',
        'url' => 'https://example.test/ar/offers', 'send_at' => '2026-11-02 09:30:00',
    ];
});

afterEach(function () {
    Carbon::setTestNow();
    DB::purge('notification_test');
});

test('website notifications are invisible before their scheduled time and delivered through the existing queue', function () {
    $response = $this->composer->store(Request::create('/', 'POST', [...$this->payload, 'users' => json_encode($this->payload['users'])]));
    $sendAt = Carbon::parse($this->payload['send_at'], config('app.timezone'));

    expect($response->getTargetUrl())->toBe(route('dashboard.website-notifications.index'))
        ->and(DB::table('jobs')->count())->toBe(0)
        ->and(ScheduledWebsiteNotification::count())->toBe(1)
        ->and(ScheduledWebsiteNotification::first()->send_at->timestamp)->toBe($sendAt->timestamp)
        ->and(ScheduledWebsiteNotification::first()->recipient_ids)->toBe([1, 2])
        ->and(DB::table('notifications')->count())->toBe(0)
        ->and(ScheduledWebsiteNotification::first()->sent_at)->toBeNull();

    (new SendScheduledWebsiteNotifications)->handle();
    expect(DB::table('notifications')->count())->toBe(0);

    Carbon::setTestNow($sendAt);
    (new SendScheduledWebsiteNotifications)->handle();

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all())->toBe([1, 2])
        ->and(ScheduledWebsiteNotification::query()->pending()->count())->toBe(0)
        ->and(ScheduledWebsiteNotification::first()->sent_at)->not->toBeNull();
    $notification = DB::table('notifications')->first();
    expect(json_decode($notification->data, true))->toMatchArray([
        'title' => 'New offer', 'body' => '<b>Plain text details</b>', 'url' => $this->payload['url'],
    ])->and($notification->read_at)->toBeNull();

    (new SendScheduledWebsiteNotifications)->handle();
    expect(DB::table('notifications')->count())->toBe(2);
});

test('invalid recipients content links and past schedules cannot queue a notification', function (array $invalid, string $field) {
    try {
        $this->composer->store(Request::create('/', 'POST', array_replace($this->payload, $invalid)));
        test()->fail('Expected validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field)
            ->and(DB::table('jobs')->count())->toBe(0)
            ->and(ScheduledWebsiteNotification::count())->toBe(0);
    }
})->with([
    'no recipients' => [['users' => []], 'users'],
    'deleted user' => [['users' => [3]], 'users.0'],
    'missing user' => [['users' => [999]], 'users.0'],
    'duplicate users' => [['users' => [1, 1]], 'users.0'],
    'wrong id type' => [['users' => ['not-a-user']], 'users.0'],
    'malformed selection' => [['users' => 'not-json'], 'users'],
    'missing title' => [['title' => ''], 'title'],
    'oversized body' => [['body' => str_repeat('a', 501)], 'body'],
    'unsafe URL' => [['url' => 'javascript:alert(1)'], 'url'],
    'past date' => [['send_at' => '2026-11-01 11:59:00'], 'send_at'],
    'invalid date' => [['send_at' => 'later'], 'send_at'],
    'invalid recipient mode' => [['recipient_mode' => 'all'], 'recipient_mode'],
]);

test('a synchronous queue cannot accidentally send a scheduled notification immediately', function () {
    config(['queue.default' => 'sync']);
    expect(fn () => $this->composer->store(Request::create('/', 'POST', $this->payload)))
        ->toThrow(ValidationException::class);
    expect(DB::table('notifications')->count())->toBe(0);
});

test('scheduled notifications respect account opt out performed after scheduling', function () {
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    DB::table('user_preferences')->insert(['user_id' => 1, 'notifications_enabled' => false]);
    Carbon::setTestNow(Carbon::parse($this->payload['send_at'], config('app.timezone')));
    (new SendScheduledWebsiteNotifications)->handle();

    expect(DB::table('notifications')->pluck('notifiable_id')->all())->toBe([2]);
});

test('dashboard messages ignore individual type exclusions while respecting the account switch', function () {
    DB::table('user_preferences')->insert([
        ['user_id' => 1, 'notifications_enabled' => true, 'notification_types' => json_encode(['disabled' => ['general', 'new_offer', 'favorite_offer_expiring']])],
        ['user_id' => 2, 'notifications_enabled' => false, 'notification_types' => null],
    ]);
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    expect(deliverComposerNotifications())->toBe([1])
        ->and(json_decode(DB::table('notifications')->first()->data, true)['data']['source'])->toBe('dashboard');
});

function composerFilter(string $field, string $operator, mixed $value): array
{
    $definition = collect(QueryFilters::resolve(User::class))->firstWhere('id', hash('sha256', $field));

    return [...$definition, 'operator' => $operator, 'value' => $value];
}

function allowComposerConditions(bool $allowed = true): void
{
    test()->actingAs(new Admin(['name' => 'Test admin', 'active' => true]), 'admins');
    Gate::shouldReceive('forUser')->andReturnSelf();
    Gate::shouldReceive('allows')->with(PermissionNameResolver::resolve('dashboard.website-notifications.recipients'))->andReturn($allowed);
}

function deliverComposerNotifications(): array
{
    Carbon::setTestNow(Carbon::parse(test()->payload['send_at'], config('app.timezone')));
    (new SendScheduledWebsiteNotifications)->handle();

    return DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all();
}

test('condition mode schedules matches directly and ignores the inactive manual selection', function () {
    allowComposerConditions();
    $rules = ['condition' => 'AND', 'rules' => [composerFilter('first_name', 'equal', 'Ahmed')]];
    $payload = [...$this->payload, 'recipient_mode' => 'condition', 'users' => ['invalid'], 'filters' => json_encode($rules)];

    $this->composer->store(Request::create('/', 'POST', $payload));

    expect(ScheduledWebsiteNotification::count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('notifications')->count())->toBe(0)
        ->and(deliverComposerNotifications())->toBe([1]);
});

test('manual mode sends only to selected users and ignores inactive conditions', function () {
    $payload = [...$this->payload, 'recipient_mode' => 'custom', 'users' => [2], 'filters' => 'invalid-json'];
    $this->composer->store(Request::create('/', 'POST', $payload));

    expect(deliverComposerNotifications())->toBe([2]);
});

test('empty invalid and unmatched conditions never schedule an audience', function (?string $filters) {
    allowComposerConditions();
    $payload = [...$this->payload, 'recipient_mode' => 'condition', 'filters' => $filters];

    expect(fn () => $this->composer->store(Request::create('/', 'POST', $payload)))->toThrow(ValidationException::class);
    expect(DB::table('jobs')->count())->toBe(0);
})->with([null, 'invalid-json', '{"condition":"AND","rules":[]}']);

test('conditions with no matching users report a validation error', function () {
    allowComposerConditions();
    $rules = ['condition' => 'AND', 'rules' => [composerFilter('first_name', 'equal', 'Nobody')]];
    $payload = [...$this->payload, 'recipient_mode' => 'condition', 'filters' => json_encode($rules)];

    expect(fn () => $this->composer->store(Request::create('/', 'POST', $payload)))->toThrow(ValidationException::class);
    expect(DB::table('jobs')->count())->toBe(0);
});

test('deleting a pending schedule prevents its delivery', function () {
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    $entry = ScheduledWebsiteNotification::firstOrFail();
    $this->composer->destroy((string) $entry->id);

    expect(ScheduledWebsiteNotification::find($entry->id))->toBeNull()
        ->and(deliverComposerNotifications())->toBe([]);
});

test('completed schedules cannot be cancelled or shown as pending', function () {
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    $entry = ScheduledWebsiteNotification::firstOrFail();
    expect(deliverComposerNotifications())->toBe([1, 2])
        ->and((new ScheduledWebsiteNotifications)->query()->count())->toBe(0);

    expect(fn () => $this->composer->destroy((string) $entry->id))
        ->toThrow(ModelNotFoundException::class);
    expect(ScheduledWebsiteNotification::find($entry->id))->not->toBeNull();
});

test('the list contains both future schedules and overdue work that has not been sent', function () {
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    $future = ScheduledWebsiteNotification::firstOrFail();
    $overdue = $future->replicate();
    $overdue->send_at = now()->subMinute();
    $overdue->save();

    expect((new ScheduledWebsiteNotifications)->query()->pluck('id')->all())->toBe([$overdue->id, $future->id]);
});

test('failed delivery rolls back inbox records and leaves the schedule available for retry', function () {
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    Carbon::setTestNow(Carbon::parse($this->payload['send_at'], config('app.timezone')));
    $sender = Notification::getFacadeRoot();
    Notification::shouldReceive('sendNow')->once()->andReturnUsing(function ($users) {
        $users->first()->notifications()->create([
            'id' => 'ab6241a0-2f7a-4617-a3a9-ef8ba194a41f',
            'type' => WebsiteNotification::class,
            'data' => ['title' => 'Incomplete delivery'],
        ]);
        throw new RuntimeException('Interrupted delivery');
    });

    expect(fn () => (new SendScheduledWebsiteNotifications)->handle())->toThrow(RuntimeException::class);
    expect(DB::table('notifications')->count())->toBe(0)
        ->and(ScheduledWebsiteNotification::first()->sent_at)->toBeNull();

    Notification::swap($sender);
    expect(deliverComposerNotifications())->toBe([1, 2]);
});

test('the dashboard entry opens the standard listing with its create action', function () {
    $this->actingAs(new Admin(['name' => 'Test admin', 'active' => true]), 'admins');
    Gate::shouldReceive('forUser')->andReturnSelf();
    Gate::shouldReceive('allows')->andReturnTrue();
    $this->composer->store(Request::create('/', 'POST', $this->payload));
    $source = str_replace(['<x-layouts::dashboard>', '</x-layouts::dashboard>'], ['<div>', '</div>'], file_get_contents(resource_path('views/dashboard/website-notifications/index.blade.php')));
    $html = Blade::render($source);

    expect($this->composer->index()->name())->toBe('dashboard.website-notifications.index')
        ->and($html)->toContain(route('dashboard.website-notifications.create'), 'New offer', 'method="delete"')
        ->and($html)->not->toContain('Incomplete delivery');
});

test('schedule deletion is hidden without its route permission', function () {
    expect((new ScheduledWebsiteNotifications)->actions()[0]->visible)->toBeFalse();
});

test('scheduled delivery is registered every minute before the existing queue worker', function () {
    $events = app(Schedule::class)->events();
    $sender = collect($events)->search(fn ($event) => $event->description === SendScheduledWebsiteNotifications::class);
    $worker = collect($events)->search(fn ($event) => str_contains($event->command ?? '', 'queue:work'));

    expect($sender)->not->toBeFalse()
        ->and($worker)->not->toBeFalse()
        ->and($events[$sender]->expression)->toBe('* * * * *')
        ->and($sender)->toBeLessThan($worker);
});

test('the schedule migration rolls back independently of inbox notifications', function () {
    (require database_path('migrations/2026_11_01_000002_create_scheduled_website_notifications_table.php'))->down();
    expect(Schema::hasTable('scheduled_website_notifications'))->toBeFalse()
        ->and(Schema::hasTable('notifications'))->toBeTrue();
});

test('condition sending still requires the existing audience filter permission', function () {
    allowComposerConditions(false);
    $rules = ['condition' => 'AND', 'rules' => [composerFilter('id', 'equal', 1)]];
    $payload = [...$this->payload, 'recipient_mode' => 'condition', 'filters' => json_encode($rules)];

    expect(fn () => $this->composer->store(Request::create('/', 'POST', $payload)))->toThrow(HttpException::class);
    expect(DB::table('jobs')->count())->toBe(0);
});

test('query builder selects only matching surviving users and does not queue notifications', function () {
    $rules = ['condition' => 'AND', 'rules' => [composerFilter('first_name', 'equal', 'Ahmed')]];
    $response = $this->composer->recipients(Request::create('/', 'POST', ['filters' => json_encode($rules)]));

    expect($response->getData(true))->toBe([['__value' => 1, '__text' => 'Ahmed One (one@example.test)']])
        ->and(DB::table('jobs')->count())->toBe(0);
});

test('query builder preserves nested AND and OR groups', function () {
    $rules = ['condition' => 'AND', 'rules' => [
        ['condition' => 'OR', 'rules' => [
            composerFilter('id', 'equal', 1), composerFilter('id', 'equal', 2),
        ]],
        composerFilter('email', 'contains', '@example.test'),
    ]];
    $response = $this->composer->recipients(Request::create('/', 'POST', ['filters' => json_encode($rules)]));
    expect(array_column($response->getData(true), '__value'))->toBe([1, 2]);
});

test('invalid query builder rules cannot select the whole audience', function (string $input) {
    expect(fn () => $this->composer->recipients(Request::create('/', 'POST', ['filters' => $input])))
        ->toThrow(ValidationException::class);
})->with([
    'invalid JSON' => 'not-json',
    'empty group' => '{"condition":"AND","rules":[]}',
    'invalid group' => '{"condition":"SQL","rules":[{}]}',
    'invalid field' => '{"condition":"AND","rules":[{"id":[],"field":"id","operator":"equal","value":1}]}',
]);

test('encrypted fields from other selectors and tampered fields are rejected', function (string $field) {
    $rule = composerFilter('id', 'equal', 1);
    $rule['field'] = $field === 'tampered' ? 'tampered' : encrypt($field);
    $rules = ['condition' => 'AND', 'rules' => [$rule]];

    expect(fn () => $this->composer->recipients(Request::create('/', 'POST', ['filters' => json_encode($rules)])))
        ->toThrow(ValidationException::class);
})->with(['tampered', 'query:select password from admins', 'field:password']);

test('all composer routes require admin authentication and route permissions', function (string $action) {
    $route = Route::getRoutes()->getByName('dashboard.website-notifications.' . $action);
    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('auth:admins')
        ->and(Route::gatherRouteMiddleware($route))->toContain(RoutePermission::class);

    $request = Request::create('/');
    $request->setRouteResolver(fn () => $route);
    $admin = new Admin(['name' => 'Test admin', 'active' => true]);
    $this->actingAs($admin, 'admins');
    Gate::shouldReceive('forUser')->once()->with($admin)->andReturnSelf();
    Gate::shouldReceive('allows')->once()->with(PermissionNameResolver::resolve($route))->andReturnFalse();
    expect(fn () => (new RoutePermission)->handle($request, fn () => response('allowed')))
        ->toThrow(HttpException::class);
})->with(['index', 'create', 'store', 'destroy', 'recipients']);

test('the composer renders shared recipient filter and date components in both languages and escapes old content', function (string $locale) {
    app()->setLocale($locale);
    $this->actingAs(new Admin(['name' => 'Test admin', 'active' => true]), 'admins');
    Gate::shouldReceive('forUser')->andReturnSelf();
    Gate::shouldReceive('allows')->andReturnTrue();
    session()->flashInput(['body' => '</textarea><script>alert(1)</script>', 'recipient_mode' => 'condition']);
    request()->setLaravelSession(session()->driver());
    $source = str_replace(['<x-layouts::dashboard>', '</x-layouts::dashboard>'], ['<div>', '</div>'], file_get_contents(resource_path('views/dashboard/website-notifications/create.blade.php')));
    $html = Blade::render($source, ['users' => User::query()]);
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//select[@id="notification-users"][@multiple][@init="tomselect"]')->length)->toBe(1)
        ->and($xpath->query('//input[@name="users"][@type="hidden"]')->length)->toBe(1)
        ->and($xpath->query('//input[@id="notification-filters"][@init="query-builder"]')->length)->toBe(1)
        ->and($xpath->query('//input[@type="radio"][@name="recipient_mode"]')->length)->toBe(2)
        ->and($xpath->query('//input[@type="radio"][@name="recipient_mode"][@value="condition"][@checked]')->length)->toBe(1)
        ->and($xpath->query('//div[@visible-when="$recipient_mode == \'custom\'"]')->length)->toBe(1)
        ->and($xpath->query('//div[@visible-when="$recipient_mode == \'condition\'"]')->length)->toBe(1)
        ->and($xpath->query('//input[@name="filters"]')->length)->toBe(1)
        ->and($xpath->query('//button[@id="select-notification-users"]')->length)->toBe(0)
        ->and($xpath->query('//input[@name="send_at"][@datetime][@init="tempus-dominus"]')->length)->toBe(1)
        ->and($xpath->query('//input[@name="_token"]')->length)->toBe(1)
        ->and($xpath->query('//script')->length)->toBe(0)
        ->and($xpath->query('//textarea[@name="body"]')->item(0)->textContent)->toBe('</textarea><script>alert(1)</script>');
})->with(['ar', 'en']);
