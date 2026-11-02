<?php

namespace App\Livewire;

use App\Enums\NotificationType;
use App\Models\PushSubscription;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    #[Locked]
    public bool $website = false;

    #[Locked]
    public bool $fullPage = false;

    public string $status = 'all';

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['all', 'unread'], true)) $this->status = 'all';
        $this->resetPage('notificationsPage');
    }

    public function markAsRead(string $id): void
    {
        $this->websiteQuery()->whereKey($id)->firstOrFail()->markAsRead();
        $this->dispatch('website-notifications-updated');
    }

    public function markAsUnread(string $id): void
    {
        $this->websiteQuery()->whereKey($id)->firstOrFail()->markAsUnread();
        $this->dispatch('website-notifications-updated');
    }

    public function markAllAsRead(): void
    {
        $this->websiteQuery()->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('website-notifications-updated');
    }

    public function visit(string $id): void
    {
        $notification = $this->websiteQuery()->whereKey($id)->firstOrFail();
        $notification->markAsRead();
        $this->dispatch('website-notifications-updated');
        $url = data_get($notification->data, 'url');
        if (! is_string($url) || trim($url) === '') return;

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme && ! in_array(strtolower($scheme), ['http', 'https'], true)) return;
        $this->redirect($url);
    }

    protected function websiteQuery(): Builder
    {
        abort_unless($this->website, 404);
        $user = auth('users')->user();
        $hash = session('push_subscription_token_hash');
        $browser = $hash ? PushSubscription::query()->where('token_hash', $hash)
            ->whereMorphedTo('notifiable', $user)->first() : null;
        $query = DatabaseNotification::query();
        if (! $user && ! $browser) return $query->whereRaw('1 = 0');

        return $query->where(function (Builder $query) use ($user, $browser) {
            if ($user) {
                $query->where(fn (Builder $query) => $query->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->getKey()));
            }
            if ($browser) {
                $query->orWhere(fn (Builder $query) => $query->where('notifiable_type', $browser->getMorphClass())->where('notifiable_id', $browser->getKey()));
            }
        });
    }

    protected function popupQuery(): Builder
    {
        return $this->websiteQuery()->where('type', WebsiteNotification::class)
            ->whereNull('popup_shown_at')->whereNull('read_at');
    }

    protected function popupAllowed(DatabaseNotification $notification): bool
    {
        if (! config('redot.notifications.push_enabled', false)) return false;
        $type = NotificationType::tryFrom($notification->data['type'] ?? NotificationType::General->value);
        if (! $type || ! $recipient = $notification->notifiable) return false;

        return (new WebsiteNotification(
            title: $notification->data['title'], data: $notification->data['data'] ?? [], type: $type,
        ))->shouldSend($recipient, 'database');
    }

    #[Renderless]
    public function pendingPopups(): array
    {
        $query = $this->popupQuery()->where(fn (Builder $query) => $query->whereNull('popup_claim_expires_at')
            ->orWhere('popup_claim_expires_at', '<=', now()))->orderBy('created_at')->orderBy('id');
        $payloads = [];
        foreach ($query->cursor() as $notification) {
            if (! $this->popupAllowed($notification)) continue;
            $payloads[] = WebsiteNotification::browserPayload($notification);
            if (count($payloads) === 20) break;
        }

        return $payloads;
    }

    #[Renderless]
    public function claimPopup(string $id): ?string
    {
        return DB::transaction(function () use ($id) {
            $notification = $this->popupQuery()->whereKey($id)->lockForUpdate()->first();
            if (! $notification || ! $this->popupAllowed($notification)
                || ($notification->popup_claim_expires_at && Carbon::parse($notification->popup_claim_expires_at)->isFuture())) return null;
            $token = (string) Str::uuid();
            $notification->forceFill(['popup_claim_token' => $token, 'popup_claim_expires_at' => now()->addSeconds(30)])->save();

            return $token;
        });
    }

    #[Renderless]
    public function confirmPopup(string $id, string $token): void
    {
        $this->websiteQuery()->where('type', WebsiteNotification::class)->whereKey($id)
            ->whereNull('popup_shown_at')->where('popup_claim_token', $token)
            ->update(['popup_shown_at' => now(), 'popup_claim_token' => null, 'popup_claim_expires_at' => null]);
    }

    #[Renderless]
    public function releasePopup(string $id, string $token): void
    {
        $this->popupQuery()->whereKey($id)->where('popup_claim_token', $token)
            ->update(['popup_claim_token' => null, 'popup_claim_expires_at' => null]);
    }

    /**
     * Refresh notifications when they change elsewhere on the page.
     */
    #[On('notifications-updated')]
    #[On('website-notifications-updated')]
    public function refresh(): void
    {
        //
    }

    /**
     * Render notifications for the current panel and display mode.
     */
    public function render()
    {
        if ($this->website) {
            $query = $this->websiteQuery();
            $unreadCount = (clone $query)->whereNull('read_at')->count();
            $totalCount = $this->fullPage ? (clone $query)->count() : null;
            if ($this->status === 'unread') $query->whereNull('read_at');
            $query->latest();
            $notifications = $this->fullPage
                ? $query->paginate(15, pageName: 'notificationsPage', total: $this->status === 'unread' ? $unreadCount : $totalCount)
                : $query->limit(10)->get();

            return view('livewire.website-notifications', compact('notifications', 'unreadCount', 'totalCount'));
        }

        $notifications = current_admin()->unreadNotifications()->latest()->limit(5)->get();

        return view('livewire.notifications', [
            'notifications' => $notifications,
        ]);
    }
}
