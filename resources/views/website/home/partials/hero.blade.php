<section class="site-hero" aria-labelledby="hero-title">
    <div class="container site-container site-hero-content">
        <div class="site-hero-copy">
            <div class="site-hero-heading">
                <span class="site-hero-label d-inline-flex align-items-center gap-2"><x-icon icon="ti ti-sparkles" /> {{ __('Saving has its ways') }}</span>
                <h1 class="site-heading" id="hero-title">
                    <span>{{ __('An offer in hand') }}</span>
                    <span>{{ __('No need to ask anyone') }}</span>
                </h1>
            </div>

            <div class="site-hero-actions">
                <p>{{ __('Offers that lower your bill.') }}<br>{{ __('Cashback after confirmation.') }}</p>

                @include('website.partials.search-form', ['id' => 'hero-search', 'submitLabel' => __('Search and save')])

                @if ($heroStores->isNotEmpty())
                    <div class="site-hero-popular d-flex align-items-center flex-wrap gap-2 mt-3">
                        <span>{{ __('Try') }}:</span>
                        @foreach ($heroStores as $store)
                            <a class="btn btn-sm" href="{{ route('website.clients.show', $store) }}">{{ $store->title }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="site-hero-cta d-flex align-items-center text-nowrap gap-2 mt-3">
                    <a href="{{ route('website.offers.index') }}" class="btn btn-outline-primary gap-2"><x-icon icon="ti ti-ticket" /> {{ __('Find an offer') }}</a>
                    <a href="{{ route('website.clients.index') }}" class="site-hero-guide d-inline-flex align-items-center gap-2">{{ __('Browse stores') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" /></a>
                </div>
            </div>
        </div>

        <div class="site-hero-art">
            <div class="site-art-arch" aria-hidden="true"></div>
            <span class="site-hero-hello">{{ __('Welcome, Afandy!') }} <span aria-hidden="true">✦</span></span>
            <figure class="site-hero-figure m-0">
                <img class="w-100 h-100 object-fit-contain" src="{{ hashed_asset('assets/images/' . (app()->getLocale() === 'ar' ? 'afandy-hero-offer-rtl.webp' : 'afandy-hero-offer-ltr.webp')) }}" width="1024" height="1536"
                    alt="{{ __('Afandy presents an offer and holds a wallet') }}" fetchpriority="high" loading="eager">
            </figure>
            <span class="site-art-star" aria-hidden="true">✦</span>
            <div class="site-hero-notes">
                @foreach ([['ti ti-ticket', __('Before you pay'), __('Look for an offer')], ['ti ti-wallet', __('After confirmation'), __('Money back to you')]] as [$icon, $caption, $label])
                    <div class="site-hero-note d-flex align-items-center gap-2">
                        <span class="site-hero-note-icon"><x-icon :icon="$icon" /></span>
                        <div><small class="d-block">{{ $caption }}</small><strong class="d-block">{{ $label }}</strong></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
