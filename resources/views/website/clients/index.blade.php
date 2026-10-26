<x-layouts::website :title="__('All stores')">
    <section class="site-stores py-4" aria-labelledby="stores-title">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-6 fw-bold mb-2" id="stores-title">{{ __('All stores') }}</h1>
                <p class="text-secondary mb-0">{{ __('Your favorite stores.') }} {{ __('Better savings.') }}</p>
            </header>

            <div class="row g-4 align-items-start">
                <aside class="site-stores-sidebar col-12 col-lg-3 align-self-start">
                    <div class="site-store-filters offcanvas-lg offcanvas-start" id="store-filters" tabindex="-1" aria-labelledby="store-filters-title">
                        <div class="offcanvas-header">
                            <h2 class="offcanvas-title h3" id="store-filters-title">{{ __('Categories') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#store-filters" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="offcanvas-body d-block p-3">
                            <h2 class="h2 mb-3 d-none d-lg-block">{{ __('Categories') }}</h2>
                            <nav class="nav flex-column gap-1" aria-label="{{ __('Categories') }}">
                                <a href="{{ route('website.clients.index', ['search' => $search !== '' ? $search : null]) }}"
                                    @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-3', 'active' => $categorySlug === ''])
                                    @if ($categorySlug === '') aria-current="page" @endif>
                                    <x-icon icon="ti ti-layout-grid" class="flex-shrink-0" />
                                    <span class="flex-fill">{{ __('All stores') }}</span>
                                    <span class="badge bg-primary-lt">{{ number_format($allStoresCount) }}</span>
                                </a>
                                @foreach ($categories as $category)
                                    <a href="{{ route('website.clients.index', ['category' => $category->slug, 'search' => $search !== '' ? $search : null]) }}"
                                        @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-3', 'active' => $categorySlug === $category->slug])
                                        @if ($categorySlug === $category->slug) aria-current="page" @endif>
                                        @if ($category->image)
                                            <img src="{{ $category->image }}" alt="" width="24" height="24" class="object-fit-contain flex-shrink-0" loading="lazy" referrerpolicy="no-referrer">
                                        @else
                                            <x-icon :icon="config('cash-afandy.category_icons.' . $category->slug, 'ti ti-category')" class="flex-shrink-0" />
                                        @endif
                                        <span class="flex-fill">{{ $category->title }}</span>
                                        <span class="badge bg-primary-lt">{{ number_format($category->clients_count) }}</span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </aside>

                <div class="col-12 col-lg-9 d-flex flex-column">
                    <div class="d-flex flex-wrap justify-content-between align-items-stretch gap-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center align-self-stretch gap-2">
                            <h2 class="h3 lh-1 mb-0">{{ $selectedCategory?->title ?? __('Stores') }}</h2>
                            <span class="badge" data-store-result-count aria-live="polite">{{ $countLabel }}</span>
                        </div>
                        <div role="search" class="site-store-search d-flex align-items-center gap-2" data-store-search>
                            <div class="input-icon flex-fill">
                                <span class="input-icon-addon text-primary" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
                                <x-input type="search" name="search" :value="$search" :placeholder="__('Search')" :aria-label="__('Store')" class="pe-5" maxlength="255" />
                                <button type="button" @class(['btn btn-icon btn-ghost-secondary position-absolute top-50 end-0 translate-middle-y me-1', 'd-none' => $search === '']) data-clear-store-search aria-label="{{ __('Reset') }}">
                                    <x-icon icon="ti ti-x" />
                                </button>
                            </div>
                            <button type="button" class="btn btn-icon btn-outline-primary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#store-filters" aria-controls="store-filters" aria-label="{{ __('Filter') }}">
                                <x-icon icon="ti ti-adjustments-horizontal" />
                            </button>
                        </div>
                    </div>

                    <div data-store-results aria-busy="false">
                        @include('website.clients.partials.results')
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
