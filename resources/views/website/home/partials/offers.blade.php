<section class="site-offers py-5" id="offers" aria-labelledby="offers-title">
    <div class="container site-container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="site-offers-eyebrow d-flex align-items-center gap-1 mb-2"><span aria-hidden="true">✦</span> {{ __("Afandy's picks") }}</div>
                <h2 class="site-heading mb-2" id="offers-title">{{ __('Worthwhile offers. Savings done right.') }}</h2>
                <p class="text-secondary mb-0">{{ __('Offers from your favorite stores. Choose what suits you.') }}</p>
            </div>
            <a href="{{ route('website.offers.index') }}" class="d-inline-flex align-items-center gap-2">
                {{ __('View all offers') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
            </a>
        </div>

        @if ($offers->isNotEmpty())
            <div class="site-offers-filters d-flex flex-wrap gap-2 mb-4" role="group" aria-label="{{ __('Filter offers') }}">
                <button type="button" class="btn btn-primary" data-offer-filter="all" aria-pressed="true">{{ __('All categories') }}</button>
                @foreach ($offerCategories as $category)
                    <button type="button" class="btn" data-offer-filter="{{ $category->slug }}" aria-pressed="false">{{ $category->title }}</button>
                @endforeach
            </div>

            <div class="site-offers-grid">
                @foreach ($offers as $offer)
                    @php($category = $offer->client->categories->first())
                    <div data-offer-category="{{ $offer->client->categories->pluck('slug')->join(' ') }}">
                        @include('website.partials.offer-card', ['categoryLabel' => $category?->title, 'categorySlug' => $category?->slug])
                    </div>
                @endforeach
            </div>
        @else
            <x-empty icon="ti ti-ticket" :title="__('No results found')" :subtitle="null" />
        @endif
    </div>
</section>
