<section class="site-partners py-5 overflow-hidden">
    <div class="container">
        <div class="site-deals-heading text-center text-md-start mb-4">
            <h2 class="mb-1">{{ __('Our Partners in Success') }}</h2>
            <p class="text-body-secondary mb-0">
                {{ __('Progressing towards achieving more accomplishments and continuous excellence') }}
            </p>
        </div>
    </div>

    <div id="partners-row-1" class="container swiper site-partners-swiper mb-3">
        <div class="swiper-wrapper align-items-center">
            @foreach ($clients->shuffle() as $client)
                <div class="swiper-slide">
                    <a class="site-partners-logo" href="{{ route('website.clients.show', $client) }}"
                        title="{{ $client->title }}">
                        <img src="{{ $client->logo }}" alt="{{ $client->title }}" loading="lazy">
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <div id="partners-row-2" class="container swiper site-partners-swiper">
        <div class="swiper-wrapper align-items-center">
            @foreach ($clients->shuffle() as $client)
                <div class="swiper-slide">
                    <a class="site-partners-logo" href="{{ route('website.clients.show', $client) }}"
                        title="{{ $client->title }}">
                        <img src="{{ $client->logo }}" alt="{{ $client->title }}" loading="lazy">
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

@push('scripts')
    <script>
        $(document).ready(() => {
            let partnersMarqueeOptions = {
                slidesPerView: 'auto',
                spaceBetween: 24,
                loop: true,
                speed: 3000,
                allowTouchMove: false,
                autoplay: {
                    delay: 1,
                    disableOnInteraction: true,
                    pauseOnMouseEnter: false,
                },
            };

            new Swiper('#partners-row-1', partnersMarqueeOptions);
            new Swiper('#partners-row-2', {
                ...partnersMarqueeOptions,
                autoplay: { ...partnersMarqueeOptions.autoplay, reverseDirection: true },
            });
        });
    </script>
@endpush
