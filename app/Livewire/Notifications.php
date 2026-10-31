<?php

namespace App\Livewire;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Notifications extends Component
{
    #[Locked]
    public bool $website = false;

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

    /**
     * Refresh the dropdown when notifications change elsewhere on the page.
     */
    #[On('notifications-updated')]
    #[On('website-notifications-updated')]
    public function refresh(): void
    {
        //
    }

    /**
     * Render the notifications dropdown.
     */
    public function render()
    {
        if ($this->website) {
            $query = $this->websiteQuery();
            $unreadCount = (clone $query)->whereNull('read_at')->count();
            $query->latest();

            return view('livewire.website-notifications', [
                'notifications' => $query->limit(10)->get(),
                'unreadCount' => $unreadCount,
            ]);
        }

        $notifications = current_admin()->unreadNotifications()->latest()->limit(5)->get();

        return view('livewire.notifications', [
            'notifications' => $notifications,
        ]);
    }
}
