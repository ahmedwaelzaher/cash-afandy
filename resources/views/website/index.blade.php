@php
    // Shared preview stores for the marquee and coupons until backend integration.
    $stores = [
        ['name' => 'noon', 'url' => 'https://www.noon.com/', 'logo' => 'https://f.nooncdn.com/s/app/com/noon/design-system/logos/noon-logo-en.svg'],
        ['name' => 'Amazon', 'url' => 'https://www.amazon.eg/', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/06/Amazon_2024.svg'],
        ['name' => 'Jumia', 'url' => 'https://www.jumia.com.eg/', 'logo' => 'https://eg.jumia.is/cms/7-20/dau-mau/Jumia-Logo_new.png'],
        ['name' => 'adidas', 'url' => 'https://www.adidas.com.eg/', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'],
        ['name' => 'Nice One', 'url' => 'https://niceonesa.com/', 'logo' => 'https://cdn.niceonesa.com/web/assets_v2/images/ltr_logo.svg?v=2.8.0'],
    ];
@endphp

<x-layouts::website class="site-home">
    @include('website.home.partials.hero')
    @include('website.home.partials.store-strip')
    @include('website.home.partials.offers')
</x-layouts::website>
