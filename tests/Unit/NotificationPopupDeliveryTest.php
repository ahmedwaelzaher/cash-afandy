<?php

use App\Enums\NotificationType;
use App\Livewire\Notifications;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\DashboardNotification;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(TestCase::class);

beforeEach(function () {
    config(['database.default' => 'popup_delivery_test',
        'database.connections.popup_delivery_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        'redot.notifications.push_enabled' => true]);
    $this->freezeTime();
    Schema::create('users', function (Blueprint $table) {
        $table->id(); $table->softDeletes();
    });
    Schema::create('countries', function (Blueprint $table) {
        $table->id();
    });
    Schema::create('user_preferences', function (Blueprint $table) {
        $table->id(); $table->unsignedBigInteger('user_id'); $table->timestamps();
    });
    (require database_path('migrations/2026_11_01_000001_add_notification_preferences_to_user_preferences.php'))->up();
    (require database_path('migrations/2026_10_28_000001_create_push_subscriptions_table.php'))->up();
    (require database_path('migrations/2026_10_29_000001_allow_guest_push_subscriptions.php'))->up();
    (require database_path('migrations/0001_01_01_000015_create_notifications_table.php'))->up();
    $this->popupMigration = require database_path('migrations/2026_11_02_000004_add_popup_delivery_to_notifications.php');
    $this->popupMigration->up();
    DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    DB::table('user_preferences')->insert([['user_id' => 1], ['user_id' => 2]]);
    $this->user = User::findOrFail(1);
    $this->actingAs($this->user, 'users');
    $this->popups = new Notifications;
    $this->popups->website = true;
});

afterEach(function () {
    $this->travelBack();
    DB::purge('popup_delivery_test');
});

function accountPopup($owner, string $title = 'New message', array $extra = [])
{
    return $owner->notifications()->create([
        'id' => (string) Str::uuid(), 'type' => WebsiteNotification::class,
        'data' => [...['title' => $title, 'body' => 'Details', 'type' => NotificationType::NewOffer->value], ...$extra],
    ]);
}

test('unshown account notifications resume in another browser and display confirmation does not mark them read', function () {
    $message = accountPopup($this->user);
    accountPopup(User::find(2), 'Other account');
    expect(array_column(array_column($this->popups->pendingPopups(), 'data'), 'notification_id'))->toBe([$message->id]);
    $token = $this->popups->claimPopup($message->id);
    expect($token)->not->toBeNull();
    $otherBrowser = new Notifications;
    $otherBrowser->website = true;
    expect($otherBrowser->claimPopup($message->id))->toBeNull();
    $this->popups->confirmPopup($message->id, $token);
    expect($otherBrowser->pendingPopups())->toBe([])
        ->and($message->fresh()->popup_shown_at)->not->toBeNull()
        ->and($message->fresh()->read_at)->toBeNull();
});

test('an abandoned browser reservation expires and its old acknowledgement cannot consume the new reservation', function () {
    $message = accountPopup($this->user);
    $oldToken = $this->popups->claimPopup($message->id);
    $this->travel(31)->seconds();
    expect($this->popups->pendingPopups())->toHaveCount(1);
    $newToken = $this->popups->claimPopup($message->id);
    expect($newToken)->not->toBe($oldToken);
    $this->popups->confirmPopup($message->id, $oldToken);
    expect($message->fresh()->popup_shown_at)->toBeNull();
    $this->popups->confirmPopup($message->id, $newToken);
    expect($message->fresh()->popup_shown_at)->not->toBeNull();
});

test('a tab becoming hidden releases its reservation without losing or reading the notification', function () {
    $message = accountPopup($this->user);
    $token = $this->popups->claimPopup($message->id);
    $this->popups->releasePopup($message->id, 'wrong-token');
    expect($this->popups->claimPopup($message->id))->toBeNull();
    $this->popups->releasePopup($message->id, $token);
    expect($this->popups->claimPopup($message->id))->not->toBeNull()
        ->and($message->fresh()->read_at)->toBeNull();
});

test('popup operations cannot access another accounts notification', function () {
    $message = accountPopup(User::find(2));
    expect($this->popups->claimPopup($message->id))->toBeNull();
    $this->popups->confirmPopup($message->id, 'forged');
    $this->popups->releasePopup($message->id, 'forged');
    expect($message->fresh()->popup_shown_at)->toBeNull()
        ->and($message->fresh()->popup_claim_token)->toBeNull();
});

test('backlog respects current account preferences while dashboard messages bypass type exclusions', function () {
    accountPopup($this->user);
    $manual = accountPopup($this->user, 'Dashboard message', ['type' => 'general', 'data' => ['source' => 'dashboard']]);
    $this->user->preferences()->update(['notification_types' => ['disabled' => ['new_offer', 'general']]]);
    auth('users')->setUser($this->user->fresh());
    expect($this->popups->pendingPopups())->toHaveCount(1)
        ->and($this->popups->pendingPopups()[0]['data']['notification_id'])->toBe($manual->id);
    $this->user->preferences()->update(['notifications_enabled' => false]);
    auth('users')->setUser($this->user->fresh());
    expect($this->popups->pendingPopups())->toBe([])
        ->and($this->popups->claimPopup($manual->id))->toBeNull();
});

test('old notifications establish a baseline without replaying history or changing read state', function () {
    $this->popupMigration->down();
    $old = accountPopup($this->user);
    $this->popupMigration->up();
    $new = accountPopup($this->user);
    expect($old->fresh()->popup_shown_at)->not->toBeNull()
        ->and($old->fresh()->read_at)->toBeNull()
        ->and($this->popups->pendingPopups()[0]['data']['notification_id'])->toBe($new->id);
});

test('read notifications and other notification classes are not replayed as website cards', function () {
    accountPopup($this->user)->markAsRead();
    $other = accountPopup($this->user);
    $other->update(['type' => DashboardNotification::class]);
    expect($this->popups->pendingPopups())->toBe([]);
});

test('guests receive only the browser bound to the current session', function () {
    auth('users')->forgetUser();
    $this->app['auth']->guard('users')->logout();
    $browser = PushSubscription::create(['token' => 'guest-browser']);
    $other = PushSubscription::create(['token' => 'other-browser']);
    $own = accountPopup($browser);
    $foreign = accountPopup($other);
    session()->put('push_subscription_token_hash', $browser->token_hash);
    expect($this->popups->pendingPopups()[0]['data']['notification_id'])->toBe($own->id)
        ->and($this->popups->claimPopup($foreign->id))->toBeNull();
});
