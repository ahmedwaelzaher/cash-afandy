<div class="dropdown site-notifications" wire:poll.15s.visible>
    <button type="button" class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ __('Notifications') }}" aria-controls="website-notifications-{{ $this->getId() }}" wire:ignore.self>
        <span class="position-relative d-inline-flex">
            <x-icon icon="ti ti-bell" class="m-0 fs-1 w-auto h-auto lh-1" />
            @if ($unreadCount)
                <span class="badge fs-6 position-absolute top-0 start-0 translate-middle rounded-pill border border-2 border-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end p-0" id="website-notifications-{{ $this->getId() }}" wire:ignore.self>
        <div class="d-flex align-items-center justify-content-between gap-2 p-3 border-bottom">
            <h2 class="h3 mb-0">{{ __('Notifications') }}</h2>
            @if ($unreadCount)
                <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary flex-shrink-0" wire:click="markAllAsRead" wire:loading.attr="disabled" aria-label="{{ __('Mark all as read') }}" title="{{ __('Mark all as read') }}" data-bs-toggle="tooltip">
                    <x-icon icon="ti ti-checks" />
                </button>
            @endif
        </div>
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @include('website.notifications.partials.notification')
            @empty
                <p class="text-secondary text-center p-4 mb-0">{{ __('No notifications yet') }}</p>
            @endforelse
        </div>
        <a class="site-search-view-all site-search-result d-flex align-items-center justify-content-between gap-2 text-reset text-decoration-none p-3" href="{{ route('website.notifications.index') }}">
            {{ __('View all notifications') }}
            <x-icon icon="ti ti-arrow-left" class="site-menu-arrow m-0" aria-hidden="true" />
        </a>
    </div>
</div>

@script
    <script>
        const refresh = () => $wire.$refresh();
        $(document).on('website:notification-received website:push-subscription-updated', refresh);
        document.addEventListener('livewire:navigating', () => {
            $(document).off('website:notification-received website:push-subscription-updated', refresh);
        }, { once: true });
    </script>
@endscript
