@if ($stores->isEmpty() && $offers->isEmpty())
    <div class="p-4 text-center" role="status">
        <x-icon icon="ti ti-search" class="mb-2" />
        <p class="fw-semibold mb-1">{{ __('No results found') }}</p>
        <p class="text-secondary mb-0">{{ __('Try searching with a different keyword') }}</p>
    </div>
@else
    @foreach ($storesFirst ? ['stores', 'offers'] : ['offers', 'stores'] as $group)
        @php($items = $group === 'stores' ? $stores : $offers)
        @if ($items->isNotEmpty())
            <section class="site-search-group" id="afandy-search-{{ $group }}" aria-label="{{ $group === 'stores' ? __('Stores') : __('Offers') }}">
                @foreach ($items as $item)
                    <a class="site-search-result d-flex align-items-center gap-3 text-reset text-decoration-none p-3"
                        href="{{ $group === 'stores' ? route('website.stores.show', $item) : route('website.offers.show', $item) }}">
                        <span class="site-search-result-visual d-flex align-items-center justify-content-center flex-shrink-0">
                            @if ($group === 'stores')
                                @if ($item->logo)
                                    <img src="{{ $item->logo }}" alt="" width="40" height="40" class="object-fit-contain" loading="lazy" referrerpolicy="no-referrer">
                                @else
                                    <x-icon icon="ti ti-building-store" />
                                @endif
                            @else
                                <strong dir="ltr">{{ $item->discount }}{{ $item->fixed_discount ? ' ' . __('EGP') : '%' }}</strong>
                            @endif
                        </span>
                        <span class="site-search-result-copy flex-fill">
                            <strong class="d-block">{{ $item->title }}</strong>
                            <small class="d-block text-secondary mt-1">
                                @if ($group === 'stores')
                                    {{ $item->available_offers_count > 0
                                        ? trans_choice('Offer count', $item->available_offers_count, ['count' => number_format($item->available_offers_count)])
                                        : __('No offers from :store yet', ['store' => $item->title]) }}
                                @else
                                    {{ $item->store->title }}
                                @endif
                            </small>
                        </span>
                        <x-icon icon="ti ti-arrow-left" class="site-menu-arrow flex-shrink-0" />
                    </a>
                @endforeach
            </section>
        @endif
    @endforeach
    <a class="site-search-view-all d-flex align-items-center justify-content-center gap-2 text-reset text-decoration-none p-3"
        href="{{ route('website.search', ['afandy_search' => $search, 'afandy_category' => $category ?? ($storesFirst ? 'stores' : 'offers'), 'country' => website_country()?->code]) }}">
        {{ __('View all') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow m-0" />
    </a>
@endif
