<x-layouts::website :title="$client->title">
    <section class="site-deals py-5">
        <div class="container">
            <div class="card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-center gap-4 text-center text-md-start">
                    <span class="site-partners-logo flex-shrink-0">
                        <img src="{{ $client->logo }}" alt="{{ $client->title }}">
                    </span>

                    <div class="flex-fill">
                        <h1 class="h2 mb-2">{{ $client->title }}</h1>

                        @if ($client->description)
                            <div class="text-body-secondary mb-2">{!! $client->description !!}</div>
                        @endif

                        @if ($client->categories->isNotEmpty())
                            <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                                @foreach ($client->categories as $category)
                                    <a href="{{ route('website.coupons.index', ['category' => $category->slug]) }}"
                                        class="badge bg-red-lt text-decoration-none">
                                        {{ $category->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="#store-coupons" class="nav-link @if ($coupons->isNotEmpty() || $cashbacks->isEmpty()) active @endif"
                        data-bs-toggle="tab" role="tab">
                        {{ __('Coupons') }}
                        <span class="badge bg-secondary-lt ms-2">{{ $coupons->count() }}</span>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a href="#store-cashbacks" class="nav-link @if ($coupons->isEmpty() && $cashbacks->isNotEmpty()) active @endif"
                        data-bs-toggle="tab" role="tab">
                        {{ __('Cashback') }}
                        <span class="badge bg-secondary-lt ms-2">{{ $cashbacks->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div id="store-coupons" role="tabpanel"
                    class="tab-pane @if ($coupons->isNotEmpty() || $cashbacks->isEmpty()) active show @endif">
                    @if ($coupons->isNotEmpty())
                        <div class="row row-cards">
                            @foreach ($coupons as $coupon)
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    @include('website.partials.coupon-card')
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty />
                    @endif
                </div>

                <div id="store-cashbacks" role="tabpanel"
                    class="tab-pane @if ($coupons->isEmpty() && $cashbacks->isNotEmpty()) active show @endif">
                    @if ($cashbacks->isNotEmpty())
                        <div class="row row-cards">
                            @foreach ($cashbacks as $cashback)
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    @include('website.partials.cashback-card')
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty />
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
