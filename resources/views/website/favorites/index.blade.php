<x-layouts::website :title="__('Favorite offers')">
    <section class="site-offers py-4" aria-labelledby="favorites-title">
        <div class="container site-container">
            <header class="mb-4">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                    <h1 class="display-6 fw-bold mb-0" id="favorites-title" tabindex="-1">{{ __('Favorite offers') }}</h1>
                </div>
                <p class="text-secondary mb-0">{{ __('Keep the offers you love in one place.') }}</p>
            </header>

            @if ($offers->isNotEmpty())
                <div class="site-offers-grid">
                    @foreach ($offers as $offer)
                        @php($category = $offer->store->categories->first())
                        <div class="position-relative h-100" data-favorite-item="{{ $offer->id }}">
                            @include('website.partials.offer-card', [
                                'favoritePage' => true,
                                'categoryLabel' => $category?->title,
                                'categorySlug' => $category?->slug,
                            ])
                            <div class="site-favorite-removed position-absolute align-items-center justify-content-center flex-column gap-2 p-3 text-center" data-favorite-removed hidden data-countdown-seconds="10">
                                <p class="visually-hidden mb-0" role="status">{{ __('Removed from favorites') }}</p>
                                <div class="site-favorite-countdown position-relative rounded-circle flex-center fs-2 fw-semibold" role="timer" aria-live="off" aria-label="{{ __('Time left to undo removal') }}">
                                    <bdi class="position-relative" dir="ltr" data-favorite-seconds>10</bdi>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary px-3" data-offer-favorite="{{ $offer->id }}"
                                    data-favorite-url="{{ route('website.favorites.store', $offer) }}" aria-pressed="false"
                                    data-favorite-login-url="{{ route('website.login') }}" data-favorite-authenticated="true"
                                    data-add-label="{{ __('Restore to favorites') }}" data-remove-label="{{ __('Remove from favorites') }}"
                                    aria-label="{{ __('Restore to favorites') }}">
                                    {{ __('Undo') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($offers->hasPages())
                    <div class="mt-4">{{ $offers->links() }}</div>
                @endif
            @endif
            <div data-favorites-empty @if ($offers->isNotEmpty()) hidden @endif>
                <x-empty icon="ti ti-heart" :title="__('No favorite offers yet')" :subtitle="__('Tap the heart on an offer to save it here.')" class="site-store-empty rounded-4 justify-content-center">
                    <a href="{{ route('website.offers.index') }}" class="btn btn-primary">{{ __('Browse offers') }}</a>
                </x-empty>
            </div>
        </div>
    </section>
    @include('website.partials.offer-reveal')
</x-layouts::website>
