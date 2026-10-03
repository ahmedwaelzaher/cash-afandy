<x-layouts::website :title="__('Search results for: :search', ['search' => $search])">
    <section class="site-deals py-5">
        <div class="container">
            <div class="site-deals-heading mb-5">
                <h2 class="mb-1">{{ __('Search results for: :search', ['search' => $search]) }}</h2>
            </div>

            @if ($stores->isEmpty() && $coupons->isEmpty() && $cashbacks->isEmpty())
                <x-empty :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" />
            @endif

            @if ($stores->isNotEmpty())
                <div class="mb-5">
                    <div class="site-deals-heading mb-4">
                        <h2 class="mb-1">{{ __('Stores') }}</h2>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3">
                        @foreach ($stores as $store)
                            <a class="site-partners-logo" href="{{ route('website.clients.show', $store) }}"
                                title="{{ $store->title }}">
                                <img src="{{ $store->logo }}" alt="{{ $store->title }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($coupons->isNotEmpty())
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
                        <div class="site-deals-heading">
                            <h2 class="mb-1">{{ __('Coupons') }}</h2>
                        </div>

                        <a href="{{ route('website.coupons.index', ['search' => $search]) }}"
                            class="site-deals-view-all text-nowrap">{{ __('View all') }}</a>
                    </div>

                    <div class="row row-cards">
                        @foreach ($coupons as $coupon)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                @include('website.partials.coupon-card')
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($cashbacks->isNotEmpty())
                <div>
                    <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
                        <div class="site-deals-heading">
                            <h2 class="mb-1">{{ __('Cashback') }}</h2>
                        </div>

                        <a href="{{ route('website.cashbacks.index', ['search' => $search]) }}"
                            class="site-deals-view-all text-nowrap">{{ __('View all') }}</a>
                    </div>

                    <div class="row row-cards">
                        @foreach ($cashbacks as $cashback)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                @include('website.partials.cashback-card')
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts::website>
