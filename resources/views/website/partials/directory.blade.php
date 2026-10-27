<section class="site-stores py-4" aria-labelledby="directory-title" data-directory>
    <div class="container site-container">
        <header class="mb-4">
            <h1 class="display-6 fw-bold mb-2" id="directory-title">{{ $directoryTitle }}</h1>
            <p class="text-secondary mb-0">{{ $directorySubtitle }}</p>
        </header>

        <div class="site-directory-layout">
            <div class="site-directory-heading d-flex flex-wrap justify-content-between align-items-stretch gap-3">
                <div class="d-flex flex-wrap align-items-center align-self-stretch gap-2">
                    <h2 class="h3 lh-1 mb-0" data-directory-label>{{ $selectedCategory?->title ?? $directoryLabel }}</h2>
                    <span class="badge" data-directory-count aria-live="polite">{{ $countLabel }}</span>
                </div>
                <div role="search" class="site-store-search d-flex align-items-center gap-2" data-directory-search>
                    <div class="input-icon flex-fill">
                        <span class="input-icon-addon text-primary" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
                        <x-input type="search" name="search" :value="$search" :placeholder="$searchPlaceholder" :aria-label="$searchPlaceholder" class="pe-5" maxlength="255" />
                        <button type="button" @class(['btn btn-icon btn-ghost-secondary position-absolute top-50 end-0 translate-middle-y me-1', 'd-none' => $search === '']) data-clear-directory-search aria-label="{{ __('Reset') }}">
                            <x-icon icon="ti ti-x" />
                        </button>
                    </div>
                </div>
            </div>

            <aside class="site-stores-sidebar align-self-start">
                <div class="site-store-filters p-lg-3" id="directory-filters">
                    <h2 class="h2 mb-3 d-none d-lg-block">{{ __('Categories') }}</h2>
                    <nav class="site-search-types nav flex-nowrap flex-lg-column gap-2 gap-lg-1" aria-label="{{ __('Categories') }}">
                        <a href="{{ route($directoryRoute, ['search' => $search !== '' ? $search : null]) }}"
                            @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-2 py-lg-3 flex-shrink-0', 'active' => $categorySlug === ''])
                            @if ($categorySlug === '') aria-current="page" @endif>
                            <x-icon icon="ti ti-layout-grid" class="flex-shrink-0" />
                            <span class="flex-fill">{{ $allItemsLabel }}</span>
                            <span class="badge">{{ number_format($allItemsCount) }}</span>
                        </a>
                        @foreach ($categories as $category)
                            <a href="{{ route($directoryRoute, ['category' => $category->slug, 'search' => $search !== '' ? $search : null]) }}"
                                @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-2 py-lg-3 flex-shrink-0', 'active' => $categorySlug === $category->slug])
                                @if ($categorySlug === $category->slug) aria-current="page" @endif>
                                <x-icon :icon="$category->icon" class="flex-shrink-0" />
                                <span class="flex-fill">{{ $category->title }}</span>
                                <span class="badge">{{ number_format($categoryCounts->get($category->id, 0)) }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div data-directory-results aria-busy="false">
                @include($resultsView)
            </div>
        </div>
    </div>
</section>
