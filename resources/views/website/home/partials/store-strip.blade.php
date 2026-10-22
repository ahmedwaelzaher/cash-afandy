@php
    // Temporary preview data; replace with the store collection when backend integration is ready.
    $stores = [
        ['name' => 'noon', 'url' => 'https://www.noon.com/', 'logo' => 'https://f.nooncdn.com/s/app/com/noon/design-system/logos/noon-logo-en.svg'],
        ['name' => 'Amazon', 'url' => 'https://www.amazon.eg/', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/06/Amazon_2024.svg'],
        ['name' => 'Jumia', 'url' => 'https://www.jumia.com.eg/', 'logo' => 'https://eg.jumia.is/cms/7-20/dau-mau/Jumia-Logo_new.png'],
        ['name' => 'adidas', 'url' => 'https://www.adidas.com.eg/', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'],
        ['name' => 'Nice One', 'url' => 'https://niceonesa.com/', 'logo' => 'https://cdn.niceonesa.com/web/assets_v2/images/ltr_logo.svg?v=2.8.0'],
    ];
@endphp

<section class="site-store-strip" aria-labelledby="store-strip-title">
    <div class="container site-container site-store-strip-content">
        <h2 id="store-strip-title">
            {{ __('Your favorite stores.') }}
            <strong class="d-inline d-md-block">{{ __('Better savings.') }}</strong>
        </h2>

        <x-marquee :duration="50" :reverse="true">
            @foreach ($stores as $store)
                <a class="site-store-logo d-flex align-items-center justify-content-center" href="{{ $store['url'] }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $store['logo'] }}" alt="{{ $store['name'] }}" width="120" height="40" loading="lazy" referrerpolicy="no-referrer">
                </a>
            @endforeach
        </x-marquee>

        {{-- The store directory will be linked during backend integration. --}}
        <span class="d-inline-flex align-items-center gap-2 text-nowrap" aria-disabled="true">
            {{ __('All stores') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
        </span>
    </div>
</section>
