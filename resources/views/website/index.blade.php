@php
    $previewCategories = [
        'fashion' => ['title' => __('Fashion'), 'icon' => 'ti ti-shirt'],
        'electronics' => ['title' => __('Electronics'), 'icon' => 'ti ti-devices'],
        'beauty' => ['title' => __('Beauty and care'), 'icon' => 'ti ti-sparkles'],
        'sports' => ['title' => __('Sports'), 'icon' => 'ti ti-barbell'],
        'home-living' => ['title' => __('Home and living'), 'icon' => 'ti ti-home'],
        'health' => ['title' => __('Health'), 'icon' => 'ti ti-heartbeat'],
        'groceries' => ['title' => __('Groceries'), 'icon' => 'ti ti-basket'],
    ];
@endphp

<x-layouts::website class="site-home">
    @include('website.home.partials.hero')
    @include('website.home.partials.store-strip')
    @include('website.home.partials.offers')
    @include('website.home.partials.categories')
</x-layouts::website>
