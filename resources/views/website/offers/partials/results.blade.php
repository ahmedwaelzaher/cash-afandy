@if ($offers->isNotEmpty())
    <div class="site-offers-grid">
        @foreach ($offers as $offer)
            @php($category = $offer->client->categories->firstWhere('slug', $categorySlug) ?? $offer->client->categories->first())
            <div>
                @include('website.partials.offer-card', [
                    'categoryLabel' => $category?->title,
                    'categorySlug' => $category?->slug,
                ])
            </div>
        @endforeach
    </div>
    @if ($offers->hasPages())
        <div class="mt-4" data-directory-pagination>{{ $offers->links() }}</div>
    @endif
@else
    <x-empty icon="ti ti-ticket" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="site-store-empty rounded-4 justify-content-center" />
@endif
