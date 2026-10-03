<x-layouts::website :title="__('Coupons')">
    <section class="site-deals py-5">
        <div class="container">
            <div class="site-deals-heading mb-4">
                <h2 class="mb-1">{{ __('Coupons') }}</h2>
                <p class="text-body-secondary mb-0">
                    {{ __('The best offers and discounts are waiting for you here') }}
                </p>
            </div>

            @include('website.partials.deals-filters', ['action' => route('website.coupons.index')])

            @if ($coupons->isNotEmpty())
                <div class="row row-cards">
                    @foreach ($coupons as $coupon)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            @include('website.partials.coupon-card')
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $coupons->links() }}
                </div>
            @else
                <x-empty />
            @endif
        </div>
    </section>
</x-layouts::website>
