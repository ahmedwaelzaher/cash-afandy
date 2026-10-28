<x-layouts::website :title="__('All stores')">
    @include('website.partials.directory', [
        'directoryRoute' => 'website.stores.index',
        'directoryTitle' => __('All stores'),
        'directorySubtitle' => __('Your favorite stores.') . ' ' . __('Better savings.'),
        'allItemsLabel' => __('All stores'),
        'allItemsCount' => $allStoresCount,
        'categoryCounts' => $categories->pluck('stores_count', 'id'),
        'searchPlaceholder' => __('Search'),
        'resultsView' => 'website.stores.partials.results',
    ])
</x-layouts::website>
