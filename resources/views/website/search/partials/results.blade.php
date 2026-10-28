@if ($stores->isEmpty() && $offers->isEmpty())
    <x-empty icon="ti ti-search" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="site-store-empty rounded-4 justify-content-center" />
@endif
@php($items = $category === 'stores' ? $stores : $offers)
@if ($items->isNotEmpty())
    <section class="mb-4" data-search-result-type="{{ $category }}" aria-labelledby="search-results-{{ $category }}">
        <div class="d-flex flex-wrap justify-content-between align-items-stretch gap-3 mb-3">
            <div class="d-flex flex-wrap align-items-center align-self-stretch gap-2">
                <h2 class="h2 lh-1 mb-0" id="search-results-{{ $category }}">{{ $category === 'stores' ? __('Stores') : __('Offers') }}</h2>
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
                            'categoryLabel' => $offer->store->categories->first()?->title,
                            'categorySlug' => $offer->store->categories->first()?->slug,
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
