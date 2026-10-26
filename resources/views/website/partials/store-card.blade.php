<a href="{{ route('website.clients.show', $store) }}" class="site-store-card card card-link rounded-4 h-100 m-0 text-reset text-decoration-none">
    <div class="card-body d-flex align-items-center gap-3 p-3">
        <span class="site-directory-logo d-flex align-items-center justify-content-center flex-shrink-0 rounded-4 p-2 fs-1">
            @if ($store->logo)
                <img src="{{ $store->logo }}" alt="" width="80" height="80" class="w-100 h-100 object-fit-contain" loading="lazy" referrerpolicy="no-referrer">
            @else
                <x-icon icon="ti ti-building-store" />
            @endif
        </span>
        <span class="flex-fill w-0">
            <span class="h3 d-block text-truncate mb-1" title="{{ $store->title }}">{{ $store->title }}</span>
            <span class="d-block text-secondary small text-truncate" title="{{ $store->categories->pluck('title')->join(' · ') }}">{{ $store->categories->take(2)->pluck('title')->join(' · ') }}@if ($store->categories->count() > 2) <bdi dir="ltr">+{{ $store->categories->count() - 2 }}</bdi>@endif</span>
        </span>
        <span class="site-store-arrow flex-center flex-shrink-0 rounded-circle" aria-hidden="true">
            <x-icon icon="ti ti-arrow-left" class="site-menu-arrow m-0" />
        </span>
    </div>
</a>
