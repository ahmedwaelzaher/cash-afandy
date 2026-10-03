<section class="site-deals site-coupons py-5 overflow-hidden">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div class="site-deals-heading">
                <h2 class="mb-1">{{ __('Latest Coupons') }}</h2>
                <p class="text-body-secondary mb-0">
                    {{ __('The best offers and discounts are waiting for you here') }}
                </p>
            </div>

            <div class="site-deals-nav d-flex gap-2">
                <button type="button" class="btn btn-icon btn-brand rounded-circle site-coupons-prev"
                    aria-label="{{ __('Previous') }}">
                    <x-icon icon="fa-solid fa-arrow-left" />
                </button>

                <button type="button" class="btn btn-icon btn-brand rounded-circle site-coupons-next"
                    aria-label="{{ __('Next') }}">
                    <x-icon icon="fa-solid fa-arrow-right" />
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="coupons-slider" class="swiper site-deals-swiper" tabindex="0">
            <div class="swiper-wrapper align-items-stretch">
                @foreach ($coupons as $coupon)
                    <div class="swiper-slide h-auto">
                        @include('website.partials.coupon-card')
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex flex-nowrap justify-content-between align-items-center gap-3 mt-3">
            <div class="swiper-pagination site-deals-pagination site-coupons-pagination position-relative flex-grow-1 overflow-hidden">
            </div>
            <a href="{{ route('website.coupons.index') }}" class="site-deals-view-all text-nowrap flex-shrink-0">{{ __('View all') }}</a>
        </div>
    </div>
</section>

@push('scripts')
    <script>
        $(document).ready(() => {
            new Swiper('#coupons-slider', {
                slidesPerView: 1.15,
                spaceBetween: 16,
                grabCursor: true,
                keyboard: {
                    enabled: true,
                },
                a11y: true,
                pagination: {
                    el: '.site-coupons-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.site-coupons-next',
                    prevEl: '.site-coupons-prev',
                },
                breakpoints: {
                    576: { slidesPerView: 2, spaceBetween: 16 },
                    768: { slidesPerView: 3, spaceBetween: 20 },
                    992: { slidesPerView: 4, spaceBetween: 20 },
                    1200: { slidesPerView: 5, spaceBetween: 24 },
                },
            });
        });
    </script>
@endpush
