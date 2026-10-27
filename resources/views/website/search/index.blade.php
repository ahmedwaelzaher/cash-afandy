<x-layouts::website :title="__('Search results for: :search', ['search' => $search])">
    <section class="site-stores py-4" aria-labelledby="search-results-title">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-6 fw-bold mb-2" id="search-results-title">{{ __('Search results for: :search', ['search' => $search]) }}</h1>
                <p class="text-secondary mb-0">
                    {{ trans_choice($category === 'stores' ? 'Store count' : 'Offer count', $counts[$category], ['count' => number_format($counts[$category])]) }}
                </p>
            </header>

            <div class="row g-4 align-items-start">
                <aside class="site-stores-sidebar col-12 col-lg-3 align-self-start">
                    <div class="site-store-filters p-lg-3" id="search-result-types">
                        <h2 class="h2 mb-3 d-none d-lg-block">{{ __('Type') }}</h2>
                        <nav class="site-search-types nav flex-nowrap flex-lg-column gap-2 gap-lg-1" aria-label="{{ __('Type') }}">
                            @foreach ([['stores', __('Stores'), 'ti ti-building-store', $counts['stores']], ['offers', __('Offers'), 'ti ti-ticket', $counts['offers']]] as [$type, $label, $icon, $count])
                                <a href="{{ route('website.search', ['afandy_search' => $search, 'afandy_category' => $type, 'country' => website_country()?->code]) }}"
                                    @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-2 py-lg-3 flex-shrink-0', 'active' => $category === $type])
                                    data-afandy-category="{{ $type }}" @if ($category === $type) aria-current="page" @endif>
                                    <x-icon :icon="$icon" class="flex-shrink-0" />
                                    <span class="flex-fill">{{ $label }}</span>
                                    <span class="badge">{{ number_format($count) }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <div class="col-12 col-lg-9">
                    @if ($stores->isEmpty() && $offers->isEmpty())
                        <x-empty icon="ti ti-search" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="site-store-empty rounded-4 justify-content-center" />
                    @endif
                    @php($items = $category === 'stores' ? $stores : $offers)
                    @if ($items->isNotEmpty())
                        <section class="mb-4" data-search-result-type="{{ $category }}" aria-labelledby="search-results-{{ $category }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-stretch gap-3 mb-3">
                                <div class="d-flex flex-wrap align-items-center align-self-stretch gap-2">
                                    <h2 class="h3 lh-1 mb-0" id="search-results-{{ $category }}">{{ $category === 'stores' ? __('Stores') : __('Offers') }}</h2>
                                    <span class="badge" data-directory-count aria-live="polite">{{ trans_choice($category === 'stores' ? 'Store count' : 'Offer count', $counts[$category], ['count' => number_format($counts[$category])]) }}</span>
                                </div>
                            </div>
                            @if ($category === 'stores')
                                <div class="site-stores-grid">
                                    @foreach ($stores as $store)
                                        @include('website.partials.store-card')
                                    @endforeach
                                </div>
                            @else
                                <div class="site-offers-grid">
                                    @foreach ($offers as $offer)
                                        <div>
                                            @include('website.partials.offer-card', [
                                                'categoryLabel' => $offer->client->categories->first()?->title,
                                                'categorySlug' => $offer->client->categories->first()?->slug,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($items->hasPages())
                                <div class="mt-4" data-search-pagination>{{ $items->links() }}</div>
                            @endif
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
