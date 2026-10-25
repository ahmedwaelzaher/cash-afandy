<section class="site-categories py-5" aria-labelledby="categories-title">
    <div class="container site-container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="site-offers-eyebrow mb-2">{{ __('Your way') }} <span aria-hidden="true">✦</span></div>
                <h2 class="site-heading mb-2" id="categories-title">{{ __('Every day, a new chance to save') }}</h2>
                <p class="text-secondary mb-0">{{ __('Choose what suits you and start saving.') }}</p>
            </div>
            <a href="{{ route('website.clients.index') }}" class="d-inline-flex align-items-center gap-2">
                {{ __('All categories') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
            </a>
        </div>

        <div class="row g-3">
            @foreach (['fashion', 'electronics', 'beauty', 'home-living', 'sports', 'groceries'] as $slug)
                @php($category = $previewCategories[$slug])
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="{{ route('website.clients.index', ['category' => $slug]) }}" class="site-category-card card card-link h-100 text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <span class="site-category-icon flex-center flex-shrink-0 rounded-4" aria-hidden="true">
                                <x-icon :icon="$category['icon']" />
                            </span>
                            <div class="flex-fill">
                                <h3 class="h2 mb-1">{{ $category['title'] }}</h3>
                                <p class="text-secondary mb-0">{{ __('Offers and coupons') }}</p>
                            </div>
                            <x-icon icon="ti ti-arrow-left" class="site-menu-arrow text-secondary flex-shrink-0" />
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
