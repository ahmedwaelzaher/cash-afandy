<x-layouts::website :title="__('Search results for: :search', ['search' => $search])">
    <section class="site-stores py-4" aria-labelledby="search-results-title" data-directory data-directory-mode="search">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-6 fw-bold mb-2" id="search-results-title" data-search-title>{{ __('Search results for: :search', ['search' => $search]) }}</h1>
                <p class="text-secondary mb-0" data-directory-summary>
                    {{ trans_choice($category === 'stores' ? 'Store count' : 'Offer count', $counts[$category], ['count' => number_format($counts[$category])]) }}
                </p>
            </header>

            <div class="row g-4 align-items-start">
                <aside class="site-stores-sidebar col-12 col-lg-3 align-self-start">
                    <div class="site-store-filters p-lg-3" id="search-result-types">
                        <h2 class="h2 mb-3 d-none d-lg-block">{{ __('Type') }}</h2>
                        <nav class="site-search-types nav flex-nowrap flex-lg-column gap-2 gap-lg-1" aria-label="{{ __('Type') }}">
                            @foreach ([['stores', __('Stores'), 'ti ti-building-store', $counts['stores']], ['offers', __('Offers'), 'ti ti-ticket', $counts['offers']]] as [$type, $label, $icon, $count])
                                <a href="{{ route('website.search', ['afandy_search' => $search, 'afandy_category' => $type, 'country' => website_country()?->code]) }}"
                                    @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-2 py-lg-3 flex-shrink-0', 'active' => $category === $type])
                                    data-afandy-category="{{ $type }}" @if ($category === $type) aria-current="page" @endif>
                                    <x-icon :icon="$icon" class="flex-shrink-0" />
                                    <span class="flex-fill">{{ $label }}</span>
                                    <span class="badge">{{ number_format($count) }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <div class="col-12 col-lg-9" data-directory-results aria-busy="false">
                    @include('website.search.partials.results')
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
