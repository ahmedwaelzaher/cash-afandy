@php
    $categories = [
        'fashion' => __('Fashion'),
        'electronics' => __('Electronics'),
        'beauty' => __('Beauty and care'),
        'sports' => __('Sports'),
        'home-living' => __('Home and living'),
        'health' => __('Health'),
        'groceries' => __('Groceries'),
    ];
    $storeCategories = [
        'noon' => ['fashion', 'electronics', 'home-living', 'groceries'],
        'Amazon' => ['electronics', 'home-living', 'health'],
        'Jumia' => ['fashion', 'electronics', 'beauty'],
        'adidas' => ['sports', 'fashion'],
        'Nice One' => ['beauty', 'health'],
    ];

    // Temporary, unsaved coupons and direct offers until backend integration.
    $previewOffers = collect(range(1, 20))->map(function ($number) use ($stores, $storeCategories) {
        $store = $stores[($number - 1) % count($stores)];
        $availableCategories = $storeCategories[$store['name']];
        $category = $availableCategories[intdiv($number - 1, count($stores)) % count($availableCategories)];
        $isDirectOffer = $number % 3 === 0;
        $coupon = new \App\Models\Coupon([
            'title' => __('Save on your next order at :store', ['store' => $store['name']]),
            'code' => $isDirectOffer ? null : 'AFANDY' . str_pad($number, 2, '0', STR_PAD_LEFT),
            'discount' => [20, 15, 25, 10, 30][($number - 1) % 5],
            'description' => __('On selected products'),
            'tips' => ($isDirectOffer ? __('The offer is applied automatically through the store link.') : __('Use the coupon code at checkout.'))
                . "\n" . __('Cannot be combined with other offers or discount codes.'),
        ]);
        $coupon->setRelation('client', new \App\Models\Client(['title' => $store['name'], 'logo' => $store['logo'], 'url' => $store['url']]));

        return [
            'coupon' => $coupon,
            'category' => $category,
            'url' => $isDirectOffer ? $store['url'] . '?' . http_build_query(['affiliate' => 'cashafandy-demo']) : null,
        ];
    });
@endphp

<section class="site-offers py-5" aria-labelledby="offers-title">
    <div class="container site-container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="site-offers-eyebrow mb-2">{{ __("Afandy's picks") }} <span aria-hidden="true">✦</span></div>
                <h2 class="site-heading mb-2" id="offers-title">{{ __('Worthwhile offers. Savings done right.') }}</h2>
                <p class="text-secondary mb-0">{{ __('Coupons and offers from your favorite stores. Choose what suits you.') }}</p>
            </div>
            <a href="{{ route('website.coupons.index') }}" class="d-inline-flex align-items-center gap-2">
                {{ __('View all offers') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
            </a>
        </div>

        <div class="site-offers-filters d-flex flex-wrap gap-2 mb-4" role="group" aria-label="{{ __('Filter offers') }}">
            <button type="button" class="btn btn-primary" data-offer-filter="all" aria-pressed="true">{{ __('All categories') }}</button>
            @foreach ($categories as $category => $label)
                <button type="button" class="btn" data-offer-filter="{{ $category }}" aria-pressed="false">{{ $label }}</button>
            @endforeach
        </div>

        <div class="site-offers-grid">
            @foreach ($previewOffers as $preview)
                <div data-offer-category="{{ $preview['category'] }}">
                    @include('website.partials.coupon-card', ['coupon' => $preview['coupon'], 'categoryLabel' => $categories[$preview['category']], 'offerUrl' => $preview['url']])
                </div>
            @endforeach
        </div>
    </div>
</section>
