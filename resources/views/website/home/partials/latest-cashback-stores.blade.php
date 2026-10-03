<section class="site-deals site-cashback-stores py-5 overflow-hidden">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div class="site-deals-heading">
                <h2 class="mb-1">{{ __('Latest Cashback Stores') }}</h2>
                <p class="text-body-secondary mb-0">
                    {{ __('Get a part of your money back on every purchase and enjoy a smarter, more rewarding shopping experience!') }}
                </p>
            </div>

            <div class="site-deals-nav d-flex gap-2">
                <button type="button" class="btn btn-icon btn-brand rounded-circle site-cashback-stores-prev"
                    aria-label="{{ __('Previous') }}">
                    <x-icon icon="fa-solid fa-arrow-left" />
                </button>

                <button type="button" class="btn btn-icon btn-brand rounded-circle site-cashback-stores-next"
                    aria-label="{{ __('Next') }}">
                    <x-icon icon="fa-solid fa-arrow-right" />
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="cashback-stores-slider" class="swiper site-deals-swiper" tabindex="0">
            <div class="swiper-wrapper align-items-stretch">
                @foreach ($cashbackStores as $cashback)
                    <div class="swiper-slide h-auto">
                        @include('website.partials.cashback-card')
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex flex-nowrap justify-content-between align-items-center gap-3 mt-3">
            <div class="swiper-pagination site-deals-pagination site-cashback-stores-pagination position-relative flex-grow-1 overflow-hidden">
            </div>
            <a href="{{ route('website.cashbacks.index') }}" class="site-deals-view-all text-nowrap flex-shrink-0">{{ __('View all') }}</a>
        </div>
    </div>
</section>

@push('scripts')
    <script>
        $(document).ready(() => {
            new Swiper('#cashback-stores-slider', {
                slidesPerView: 1.15,
                spaceBetween: 16,
                grabCursor: true,
                keyboard: {
                    enabled: true,
                },
                a11y: true,
                pagination: {
                    el: '.site-cashback-stores-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.site-cashback-stores-next',
                    prevEl: '.site-cashback-stores-prev',
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
