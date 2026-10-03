<x-layouts::website :title="$cashback->client->title">
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                                <span class="site-partners-logo flex-shrink-0">
                                    <img src="{{ $cashback->client->logo }}" alt="{{ $cashback->client->title }}">
                                </span>

                                <div class="flex-fill">
                                    <div class="text-body-secondary">{{ __('Cashback') }}</div>
                                    <h1 class="h2 mb-0">{{ $cashback->client->title }}</h1>
                                </div>

                                <span class="badge bg-primary text-primary-fg fs-3 px-3 py-2">
                                    {{ __('Up to :percentage%', ['percentage' => $cashback->percentage]) }}
                                </span>
                            </div>

                            @if (! empty($cashback->details))
                                <div class="table-responsive mb-4">
                                    <table class="table table-vcenter card-table border">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Category') }}</th>
                                                <th class="w-1 text-nowrap">{{ __('Cashback') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($cashback->details as $detail)
                                                <tr>
                                                    <td>{{ $detail['category'] }}</td>
                                                    <td class="fw-bold text-nowrap">{{ $detail['value'] }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if ($cashback->how_it_works)
                                <h3>{{ __('How It Works') }}</h3>
                                <div class="mb-4">{!! $cashback->how_it_works !!}</div>
                            @endif

                            @if ($cashback->terms)
                                <h3>{{ __('Terms') }}</h3>
                                <div class="mb-4">{!! $cashback->terms !!}</div>
                            @endif

                            @if ($cashback->tips)
                                <h3>{{ __('Tips') }}</h3>
                                <div>{!! $cashback->tips !!}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <a href="{{ $cashback->url }}" target="_blank" rel="noopener noreferrer"
                                class="btn btn-brand w-100 mb-4">
                                {{ __('Get cashback') }}
                            </a>

                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Cashback') }}</div>
                                    <div class="datagrid-content">
                                        {{ __('Up to :percentage%', ['percentage' => $cashback->percentage]) }}
                                    </div>
                                </div>

                                @if (! is_null($cashback->verification_period))
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('Verification Period (days)') }}</div>
                                        <div class="datagrid-content">{{ $cashback->verification_period }}</div>
                                    </div>
                                @endif

                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Expiration Date') }}</div>
                                    <div class="datagrid-content">
                                        {{ $cashback->expiration_date?->translatedFormat('d M Y') ?? __('No expiry') }}
                                    </div>
                                </div>

                                <div class="datagrid-item">
                                    <div class="datagrid-title">{{ __('Available in') }}</div>
                                    <div class="datagrid-content">
                                        <span class="flag flag-country-{{ $cashback->country->code }}"></span>
                                        {{ app()->getLocale() === 'ar' ? $cashback->country->native : $cashback->country->name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($relatedCashbacks->isNotEmpty())
        <section class="site-deals pb-5">
            <div class="container">
                <div class="site-deals-heading mb-4">
                    <h2 class="mb-1">{{ __('Similar Cashback Stores') }}</h2>
                </div>

                <div class="row row-cards">
                    @foreach ($relatedCashbacks as $cashback)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            @include('website.partials.cashback-card')
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::website>
