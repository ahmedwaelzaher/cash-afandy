@php
    $unread = $notification->unread();
    $readAction = $unread ? 'markAsRead' : 'markAsUnread';
    $readLabel = $unread ? __('Mark as read') : __('Mark as unread');
@endphp

<div @class(['list-group-item d-flex align-items-center gap-2', 'bg-primary-lt' => $unread]) wire:key="notification-{{ $notification->id }}">
    <button type="button" class="flex-fill w-0 text-start border-0 bg-transparent p-0" wire:click="visit('{{ $notification->id }}')" wire:loading.attr="disabled">
        <span class="d-block fw-semibold text-body">{{ data_get($notification->data, 'title') ?: __('Notification') }}</span>
        @if (data_get($notification->data, 'body'))
            <small class="d-block text-secondary text-truncate">{{ data_get($notification->data, 'body') }}</small>
        @endif
        @if ($notification->created_at)
            <small class="d-flex align-items-center gap-1 text-secondary mt-1">
                <x-icon icon="ti ti-clock" class="m-0" aria-hidden="true" />
                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans(short: true) }}</time>
            </small>
        @endif
    </button>
    <button type="button" @class(['status-dot border border-primary', 'bg-primary' => $unread, 'bg-transparent' => ! $unread, 'p-0 rounded-circle cursor-pointer flex-shrink-0']) wire:click="{{ $readAction }}('{{ $notification->id }}')" wire:loading.attr="disabled" aria-label="{{ $readLabel }}" title="{{ $readLabel }}" data-bs-toggle="tooltip"></button>
</div>
