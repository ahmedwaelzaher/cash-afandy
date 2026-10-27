<x-layouts::website :title="__('All offers')">
    @include('website.partials.directory', [
        'directoryRoute' => 'website.offers.index',
        'directoryTitle' => __('All offers'),
        'directorySubtitle' => __('The best offers and discounts are waiting for you here'),
        'directoryLabel' => __('Offers'),
        'allItemsLabel' => __('All offers'),
        'allItemsCount' => $allOffersCount,
        'searchPlaceholder' => __('Search for a store or offer'),
        'resultsView' => 'website.offers.partials.results',
    ])
    @include('website.partials.offer-reveal')
</x-layouts::website>
