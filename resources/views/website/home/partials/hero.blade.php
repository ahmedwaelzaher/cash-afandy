<section class="site-hero" aria-labelledby="hero-title">
    <div class="container site-container site-hero-content">
        <div class="site-hero-copy">
            <div class="site-hero-heading">
                <span class="site-hero-label d-inline-flex align-items-center gap-2"><x-icon icon="ti ti-sparkles" /> {{ __('Saving has its ways') }}</span>
                <h1 class="site-heading" id="hero-title">
                    <span>{{ __('A coupon in hand') }}</span>
                    <span>{{ __('No need to ask anyone') }}</span>
                </h1>
            </div>

            <div class="site-hero-actions">
                <p>{{ __('Coupons that lower your bill.') }}<br>{{ __('Cashback after confirmation.') }}</p>

                @include('website.partials.search-form', ['id' => 'hero-search', 'submitLabel' => __('Search and save')])

                {{-- Temporary sample stores for reviewing the suggestions layout. --}}
                <div class="site-hero-popular d-flex align-items-center flex-wrap gap-2 mt-3">
                    <span>{{ __('Try') }}:</span>
                    @foreach (['Amazon', 'Noon', 'Jumia'] as $store)
                        <a class="btn btn-sm" href="{{ route('website.search', ['search' => $store]) }}">{{ $store }}</a>
                    @endforeach
                </div>

                <div class="site-hero-cta d-flex align-items-center text-nowrap gap-2 mt-3">
                    <a href="{{ route('website.coupons.index') }}" class="btn btn-outline-primary gap-2"><x-icon icon="ti ti-ticket" /> {{ __('Find a coupon') }}</a>
                    <span class="site-hero-guide d-inline-flex align-items-center gap-2" aria-disabled="true">{{ __('How does cashback work?') }} <x-icon icon="ti ti-arrow-down" /></span>
                </div>
            </div>
        </div>

        <div class="site-hero-art">
            <div class="site-art-arch" aria-hidden="true"></div>
            <span class="site-hero-hello">{{ __('Welcome, Afandy!') }} <span aria-hidden="true">✦</span></span>
            <figure class="site-hero-figure m-0">
                <img src="{{ hashed_asset('assets/images/afandy-hero-coupon-open-eyes.webp') }}" width="1024" height="1536"
                    alt="{{ __('Afandy presents a coupon and holds a wallet') }}" fetchpriority="high" loading="eager">
            </figure>
            <span class="site-art-star" aria-hidden="true">✦</span>
            <div class="site-hero-notes">
                @foreach ([['ti ti-ticket', __('Before you pay'), __('Look for a coupon')], ['ti ti-wallet', __('After confirmation'), __('Money back to you')]] as [$icon, $caption, $label])
                    <div class="site-hero-note d-flex align-items-center gap-2">
                        <span class="site-hero-note-icon"><x-icon :icon="$icon" /></span>
                        <div><small class="d-block">{{ $caption }}</small><strong class="d-block">{{ $label }}</strong></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
