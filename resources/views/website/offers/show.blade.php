@php
    $discount = $offer->fixed_discount ? $offer->discount . ' ' . __('EGP') : $offer->discount . '%';
@endphp

<x-layouts::website :title="$offer->title">
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                                <span class="site-partners-logo flex-shrink-0">
                                    <img src="{{ $offer->client->logo }}" alt="{{ $offer->client->title }}">
                                </span>

                                <div class="flex-fill">
                                    <div class="text-body-secondary">{{ $offer->client->title }}</div>
                                    <h1 class="h2 mb-0">{{ $offer->title }}</h1>
                                </div>

                                <span class="badge bg-primary text-primary-fg fs-3 px-3 py-2">{{ $discount }}</span>
                            </div>

                            @if ($offer->description)
                                <div class="mb-4">{!! $offer->description !!}</div>
                            @endif

                            @if ($offer->tips)
                                <h3>{{ __('Tips') }}</h3>
                                <div>{!! $offer->tips !!}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">{{ __('Offer code') }}</h3>

                            <div class="mb-3">
                                @include('website.partials.offer-code')
                            </div>

                            <a href="{{ $offer->client->url }}" target="_blank" rel="noopener noreferrer"
                                class="btn btn-outline-brand w-100 mb-4">
                                {{ __('Go to store') }}
                            </a>

                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Discount') }}</div>
                                    <div class="datagrid-content">{{ $discount }}</div>
                                </div>

                                @if ($offer->minimum_amount)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Minimum amount') }}</div>
                                        <div class="datagrid-content">{{ $offer->minimum_amount }} {{ __('EGP') }}</div>
                                    </div>
                                @endif

                                @if ($offer->maximum_amount)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Maximum amount') }}</div>
                                        <div class="datagrid-content">{{ $offer->maximum_amount }} {{ __('EGP') }}</div>
                                    </div>
                                @endif

                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Expiration Date') }}</div>
                                    <div class="datagrid-content">
                                        {{ $offer->expiration_date?->translatedFormat('d M Y') ?? __('No expiry') }}
                                    </div>
                                </div>

                                @if ($offer->countries->isNotEmpty())
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Available in') }}</div>
                                        <div class="datagrid-content">
                                            @foreach ($offer->countries as $country)
                                                <span class="flag flag-country-{{ $country->code }}"
                                                    title="{{ app()->getLocale() === 'ar' ? $country->native : $country->name }}"></span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($relatedOffers->isNotEmpty())
        <section class="site-deals pb-5">
            <div class="container">
                <div class="site-deals-heading mb-4">
                    <h2 class="mb-1">{{ __('More from :store', ['store' => $offer->client->title]) }}</h2>
                </div>

                <div class="site-offers-grid">
                    @foreach ($relatedOffers as $offer)
                        <div>
                            @include('website.partials.offer-card')
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts::website>
