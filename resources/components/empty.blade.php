@props([
    'icon' => 'fas fa-circle-xmark',
    'title' => __('Nothing to show here'),
    'subtitle' => __('Trust me, I\'ve looked everywhere, there\'s nothing here :('),
])

<div {{ $attributes->class(['card']) }}>
    <div class="empty">
        @if ($icon)
            <div class="empty-icon">
                <x-icon :icon="$icon" class="fa-3x" />
            </div>
        @endif

        <p class="empty-title">
            {{ $title }}
        </p>

        @if ($subtitle)
            <p class="empty-subtitle text-secondary">
                {{ $subtitle }}
            </p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="empty-action">{{ $slot }}</div>
        @endif
    </div>
</div>
