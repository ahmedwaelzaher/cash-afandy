@php
    $previewCategories = [
        'fashion' => ['title' => __('Fashion')],
        'electronics' => ['title' => __('Electronics')],
        'beauty' => ['title' => __('Beauty and care')],
        'sports' => ['title' => __('Sports')],
        'home-living' => ['title' => __('Home and living')],
        'health' => ['title' => __('Health')],
        'groceries' => ['title' => __('Groceries')],
    ];
@endphp

<x-layouts::website class="site-home">
    @include('website.home.partials.hero')
    @include('website.home.partials.store-strip')
    @include('website.home.partials.offers')
    @include('website.home.partials.categories')
</x-layouts::website>
