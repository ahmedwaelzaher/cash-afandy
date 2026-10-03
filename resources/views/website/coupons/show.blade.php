@php
    $discount = $coupon->fixed_discount ? $coupon->discount . ' ' . __('EGP') : $coupon->discount . '%';
@endphp

<x-layouts::website :title="$coupon->title">
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                                <span class="site-partners-logo flex-shrink-0">
                                    <img src="{{ $coupon->client->logo }}" alt="{{ $coupon->client->title }}">
                                </span>

                                <div class="flex-fill">
                                    <div class="text-body-secondary">{{ $coupon->client->title }}</div>
                                    <h1 class="h2 mb-0">{{ $coupon->title }}</h1>
                                </div>

                                <span class="badge bg-primary text-primary-fg fs-3 px-3 py-2">{{ $discount }}</span>
                            </div>

                            @if ($coupon->description)
                                <div class="mb-4">{!! $coupon->description !!}</div>
                            @endif

                            @if ($coupon->tips)
                                <h3>{{ __('Tips') }}</h3>
                                <div>{!! $coupon->tips !!}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">{{ __('Coupon code') }}</h3>

                            <div class="site-coupon-code d-flex align-items-center justify-content-between gap-2 mb-3">
                                <span class="fw-bold fs-3 text-break">{{ $coupon->code }}</span>

                                <button type="button" class="btn btn-brand flex-shrink-0" data-coupon-code="{{ $coupon->code }}">
                                    <i class="fa fa-copy me-2"></i>
                                    {{ __('Copy') }}
                                </button>
                            </div>

                            <a href="{{ $coupon->client->url }}" target="_blank" rel="noopener noreferrer"
                                class="btn btn-outline-brand w-100 mb-4">
                                {{ __('Go to store') }}
                            </a>

                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Discount') }}</div>
                                    <div class="datagrid-content">{{ $discount }}</div>
                                </div>

                                @if ($coupon->minimum_amount)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Minimum amount') }}</div>
                                        <div class="datagrid-content">{{ $coupon->minimum_amount }} {{ __('EGP') }}</div>
                                    </div>
                                @endif

                                @if ($coupon->maximum_amount)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Maximum amount') }}</div>
                                        <div class="datagrid-content">{{ $coupon->maximum_amount }} {{ __('EGP') }}</div>
                                    </div>
                                @endif

                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Expiration Date') }}</div>
                                    <div class="datagrid-content">
                                        {{ $coupon->expiration_date?->translatedFormat('d M Y') ?? __('No expiry') }}
                                    </div>
                                </div>

                                @if ($coupon->countries->isNotEmpty())
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Available in') }}</div>
                                        <div class="datagrid-content">
                                            @foreach ($coupon->countries as $country)
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

    @if ($relatedCoupons->isNotEmpty())
        <section class="site-deals pb-5">
            <div class="container">
                <div class="site-deals-heading mb-4">
                    <h2 class="mb-1">{{ __('More from :store', ['store' => $coupon->client->title]) }}</h2>
                </div>

                <div class="row row-cards">
                    @foreach ($relatedCoupons as $coupon)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            @include('website.partials.coupon-card')
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @push('scripts')
        <script>
            $(document).ready(() => {
                $('[data-coupon-code]').on('click', function () {
                    copyToClipboard($(this).attr('data-coupon-code'))
                        .then(() => toastify().success(@json(__('Coupon code copied'))));
                });
            });
        </script>
    @endpush
</x-layouts::website>
