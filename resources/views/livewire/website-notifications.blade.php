<div @class(['dropdown site-notifications' => ! $fullPage, 'site-notifications-page' => $fullPage]) wire:poll.15s.visible>
    @if (! $fullPage)
        <button type="button" class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ __('Notifications') }}" aria-controls="website-notifications-{{ $this->getId() }}" wire:ignore.self>
            <span class="position-relative d-inline-flex">
                <x-icon icon="ti ti-bell" class="m-0 fs-1 w-auto h-auto lh-1" />
                @if ($unreadCount)
                    <span class="badge fs-6 position-absolute top-0 start-0 translate-middle rounded-pill border border-2 border-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </span>
        </button>
    @endif

    <div @class(['dropdown-menu dropdown-menu-end p-0' => ! $fullPage]) id="website-notifications-{{ $this->getId() }}" wire:ignore.self>
        <div @class(['d-flex align-items-center justify-content-between', 'gap-2 p-3 border-bottom' => ! $fullPage, 'flex-wrap gap-3 mb-3' => $fullPage])>
            @if ($fullPage)
                <nav class="nav nav-segmented" aria-label="{{ __('Notifications') }}">
                    @foreach (['all' => __('All'), 'unread' => __('Unread')] as $key => $label)
                        <button type="button" @class(['nav-link', 'active' => $status === $key]) wire:click="$set('status', '{{ $key }}')" aria-pressed="{{ $status === $key ? 'true' : 'false' }}">
                            {{ $label }}
                            <span @class(['badge rounded-pill', 'bg-primary text-primary-fg' => $status === $key])>{{ number_format($key === 'all' ? $totalCount : $unreadCount) }}</span>
                        </button>
                    @endforeach
                </nav>
            @else
                <h2 class="h3 mb-0">{{ __('Notifications') }}</h2>
            @endif
            @if ($unreadCount)
                <button type="button" @class(['btn btn-sm', 'btn-icon btn-ghost-secondary flex-shrink-0' => ! $fullPage, 'btn-link text-primary text-decoration-none gap-2 p-0' => $fullPage]) wire:click="markAllAsRead" wire:loading.attr="disabled" aria-label="{{ __('Mark all as read') }}"
                    @if (! $fullPage) title="{{ __('Mark all as read') }}" data-bs-toggle="tooltip" @endif>
                    <x-icon icon="ti ti-checks" :class="$fullPage ? 'm-0' : null" />
                    @if ($fullPage)
                        {{ __('Mark all as read') }}
                    @endif
                </button>
            @endif
        </div>
        <div @class(['list-group list-group-flush' => ! $fullPage, 'd-flex flex-column gap-3' => $fullPage])>
            @forelse ($notifications as $notification)
                @include('website.notifications.partials.notification')
            @empty
                @if ($fullPage)
                    <x-empty icon="ti ti-bell" :title="__('No notifications yet')" :subtitle="null" />
                @else
                    <p class="text-secondary text-center p-4 mb-0">{{ __('No notifications yet') }}</p>
                @endif
            @endforelse
        </div>
        @if ($fullPage && $notifications->hasPages())
            <div class="mt-3">{{ $notifications->links('components.pagination', ['livewirePagination' => true]) }}</div>
        @elseif (! $fullPage)
            <a class="site-search-view-all site-search-result d-flex align-items-center justify-content-between gap-2 text-reset text-decoration-none p-3" href="{{ route('website.notifications.index') }}">
                {{ __('View all notifications') }}
                <x-icon icon="ti ti-arrow-left" class="site-menu-arrow m-0" aria-hidden="true" />
            </a>
        @endif
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
