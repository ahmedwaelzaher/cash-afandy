<x-layouts::website>
    @if ($sliders->isNotEmpty())
        @include('website.home.partials.hero-slider')
    @endif

    @if ($coupons->isNotEmpty())
        @include('website.home.partials.latest-coupons')
    @endif

    @if ($cashbackStores->isNotEmpty())
        @include('website.home.partials.latest-cashback-stores')
    @endif

    @include('website.home.partials.how-to-get-cashback')

    @if ($clients->isNotEmpty())
        @include('website.home.partials.our-partners')
    @endif

    @if (! empty($testimonials))
        @include('website.home.partials.testimonials')
    @endif
</x-layouts::website>
