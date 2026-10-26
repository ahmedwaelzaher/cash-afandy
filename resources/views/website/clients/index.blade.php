<x-layouts::website :title="__('All stores')">
    <section class="site-stores py-4" aria-labelledby="stores-title">
        <div class="container site-container">
            <div class="row g-4 align-items-start">
                <aside class="site-stores-sidebar col-12 col-lg-3">
                    <div class="offcanvas-lg offcanvas-start" id="store-filters" tabindex="-1" aria-labelledby="store-filters-title">
                        <div class="offcanvas-header">
                            <h2 class="offcanvas-title h3" id="store-filters-title">{{ __('Categories') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#store-filters" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="offcanvas-body d-block p-lg-0">
                            <h2 class="h3 mb-3 d-none d-lg-block">{{ __('Categories') }}</h2>
                            <nav class="nav flex-column gap-2" aria-label="{{ __('Categories') }}">
                                <a href="{{ route('website.clients.index', ['search' => $search !== '' ? $search : null]) }}"
                                    @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-3', 'active' => $categorySlug === ''])
                                    @if ($categorySlug === '') aria-current="page" @endif>
                                    <x-icon icon="ti ti-layout-grid" class="flex-shrink-0" />
                                    {{ __('All stores') }}
                                </a>
                                @foreach ($categories as $category)
                                    <a href="{{ route('website.clients.index', ['category' => $category->slug, 'search' => $search !== '' ? $search : null]) }}"
                                        @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-3', 'active' => $categorySlug === $category->slug])
                                        @if ($categorySlug === $category->slug) aria-current="page" @endif>
                                        @if ($category->image)
                                            <img src="{{ $category->image }}" alt="" width="24" height="24" class="object-fit-contain flex-shrink-0" loading="lazy" referrerpolicy="no-referrer">
                                        @else
                                            <x-icon icon="ti ti-category" class="flex-shrink-0" />
                                        @endif
                                        {{ $category->title }}
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </aside>

                <div class="col-12 col-lg-9">
                    <div class="mb-4">
                        <h1 class="mb-2" id="stores-title">{{ __('All stores') }}</h1>
                        <p class="text-secondary mb-0">{{ __('Your favorite stores.') }} {{ __('Better savings.') }}</p>
                    </div>

                    <form action="{{ route('website.clients.index') }}" method="GET" role="search" class="d-flex flex-wrap gap-2 mb-4">
                        @if ($categorySlug !== '')
                            <input type="hidden" name="category" value="{{ $categorySlug }}">
                        @endif
                        <div class="input-icon flex-fill">
                            <span class="input-icon-addon" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
                            <x-input type="search" name="search" :value="$search" :placeholder="__('Search')" :aria-label="__('Store')" class="ps-5" />
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                        <button type="button" class="btn btn-outline-primary d-lg-none gap-2" data-bs-toggle="offcanvas" data-bs-target="#store-filters" aria-controls="store-filters">
                            <x-icon icon="ti ti-adjustments-horizontal" /> {{ __('Filter') }}
                        </button>
                        @if ($search !== '' || $categorySlug !== '')
                            <a href="{{ route('website.clients.index') }}" class="btn btn-icon btn-outline-secondary" aria-label="{{ __('Reset') }}"><x-icon icon="ti ti-refresh" /></a>
                        @endif
                    </form>

                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h3 mb-0">{{ $selectedCategory?->title ?? __('Stores') }}</h2>
                        <span class="badge bg-primary-lt">{{ number_format($stores->total()) }}</span>
                    </div>

                    @if ($stores->isNotEmpty())
                        <div class="site-stores-grid">
                            @foreach ($stores as $store)
                                @include('website.partials.store-card')
                            @endforeach
                        </div>
                        @if ($stores->hasPages())
                            <div class="mt-4">{{ $stores->links() }}</div>
                        @endif
                    @else
                        <x-empty icon="ti ti-building-store" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="rounded-4">
                            @if ($search !== '' || $categorySlug !== '')
                                <a href="{{ route('website.clients.index') }}" class="btn btn-primary">{{ __('All stores') }}</a>
                            @endif
                        </x-empty>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
