<x-layouts::website :title="__('All stores')">
    @include('website.partials.directory', [
        'directoryRoute' => 'website.clients.index',
        'directoryTitle' => __('All stores'),
        'directorySubtitle' => __('Your favorite stores.') . ' ' . __('Better savings.'),
        'directoryLabel' => __('Stores'),
        'allItemsLabel' => __('All stores'),
        'allItemsCount' => $allStoresCount,
        'categoryCounts' => $categories->pluck('clients_count', 'id'),
        'searchPlaceholder' => __('Search'),
        'resultsView' => 'website.clients.partials.results',
    ])
</x-layouts::website>
