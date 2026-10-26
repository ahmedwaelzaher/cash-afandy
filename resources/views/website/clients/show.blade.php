@php
    $hasCoupons = $coupons->contains(fn ($coupon) => filled($coupon->code));
    $hasOffers = $coupons->contains(fn ($coupon) => blank($coupon->code));
@endphp

<x-layouts::website :title="$client->title">
    <section class="site-store py-4" aria-labelledby="store-title">
        <div class="container site-container">
            <div class="card rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-stretch gap-4 p-4">
                    <div class="d-flex align-items-stretch gap-3 flex-fill text-break">
                        <span class="avatar avatar-xl rounded-4 flex-shrink-0 bg-transparent">
                            @if ($client->logo)
                                <img src="{{ $client->logo }}" alt="" class="w-100 h-100 object-fit-contain p-2" loading="eager" referrerpolicy="no-referrer">
                            @else
                                <x-icon icon="ti ti-building-store" />
                            @endif
                        </span>
                        <div class="d-flex flex-column align-items-start">
                            <h1 class="h2 mb-2" id="store-title">{{ __('Coupons and offers from :store', ['store' => $client->title]) }}</h1>
                            @if ($client->description)
                                <p class="text-secondary mb-2">{{ \Illuminate\Support\Str::limit(strip_tags($client->description), 160) }}</p>
                            @endif
                            @if ($client->categories->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mt-auto">
                                    @foreach ($client->categories as $category)
                                        <a href="{{ route('website.clients.index', ['category' => $category->slug]) }}" class="site-offer-label text-decoration-none">{{ $category->title }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-column justify-content-between gap-3 flex-shrink-0">
                        <div class="d-flex justify-content-md-end gap-2">
                            <button type="button" class="btn btn-icon btn-outline-secondary" data-share-url="{{ route('website.clients.show', $client) }}"
                                data-share-title="{{ $client->title }}" data-share-success="{{ __('Link copied') }}"
                                data-share-error="{{ __('Unable to share or copy the link. Please try again.') }}" aria-label="{{ __('Share') }}">
                                <x-icon icon="ti ti-share" />
                            </button>
                            <button type="button" class="site-offer-favorite btn btn-icon btn-outline-secondary" data-coupon-favorite aria-pressed="false" aria-label="{{ __('Favorite') }}">
                                <x-icon icon="ti ti-heart" />
                            </button>
                        </div>
                        @if ($client->url)
                            <a href="{{ $client->url }}" class="btn btn-primary gap-2 px-4" target="_blank" rel="noopener noreferrer">
                                {{ __('Visit store') }} <x-icon icon="ti ti-external-link" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <header class="site-offers bg-transparent mb-4">
                <h2 class="site-heading mb-0" id="store-offers-title">{{ $coupons->isNotEmpty() ? __('Save with :store', ['store' => $client->title]) : __('Coupons and offers from :store', ['store' => $client->title]) }}</h2>
                @if ($coupons->isNotEmpty())
                    <p class="text-secondary mt-2 mb-0">{{ __('Choose an offer and review its terms before shopping.') }}</p>
                @endif
            </header>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-8">
                    <section class="site-offers bg-transparent" aria-labelledby="store-offers-title" data-offer-filter-attribute="data-offer-type">
                        @if ($coupons->isNotEmpty())
                            <div class="site-offers-filters d-flex flex-wrap gap-2 mb-3" role="group" aria-label="{{ __('Filter offers') }}">
                                <button type="button" class="btn btn-primary" data-offer-filter="all" aria-pressed="true">{{ __('All') }}</button>
                                <button type="button" class="btn" data-offer-filter="coupon" aria-pressed="false" @disabled(! $hasCoupons)>{{ __('Coupons') }}</button>
                                <button type="button" class="btn" data-offer-filter="offer" aria-pressed="false" @disabled(! $hasOffers)>{{ __('Offers') }}</button>
                            </div>
                            <div class="site-offers-grid">
                                @php($category = $client->categories->first())
                                @foreach ($coupons as $coupon)
                                    <div data-offer-type="{{ filled($coupon->code) ? 'coupon' : 'offer' }}">
                                        @include('website.partials.coupon-card', [
                                            'categoryLabel' => $category?->title,
                                            'categorySlug' => $category?->slug,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-empty icon="ti ti-ticket" :title="__('No offers from :store yet', ['store' => $client->title])"
                                :subtitle="__('New offers and coupons will appear here when available.')" class="rounded-4">
                                <a href="{{ route('website.coupons.index') }}" class="btn btn-primary gap-2">
                                    {{ __('Browse other store offers') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
                                </a>
                            </x-empty>
                        @endif
                    </section>
                </div>

                <aside class="col-12 col-lg-4" aria-labelledby="store-about-title">
                    <div class="card rounded-4">
                        <div class="card-body p-4">
                            <h2 class="h3 mb-3" id="store-about-title">{{ __('About :app', ['app' => $client->title]) }}</h2>
                            @if ($client->description)
                                <div class="text-secondary text-break">{!! $client->description !!}</div>
                            @else
                                <p class="text-secondary mb-0">{{ __('Explore the store and check the offer terms before shopping.') }}</p>
                            @endif

                            @if ($hasCoupons)
                                <hr>
                                <h3 class="h3 mb-3">{{ __('How to use a coupon?') }}</h3>
                                <ol class="list-unstyled d-flex flex-column gap-3 mb-0">
                                    @foreach ([__('Copy code'), __('Go to store'), __('Paste the code at checkout')] as $step)
                                        <li class="d-flex align-items-center gap-3">
                                            <span class="avatar avatar-sm rounded-circle bg-primary-lt flex-shrink-0" aria-hidden="true">{{ $loop->iteration }}</span>
                                            <span class="text-secondary">{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts::website>
