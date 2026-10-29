@php($isFavorited = (bool) ($offer->is_favorited ?? false))
<button type="button" class="site-offer-favorite btn btn-icon btn-link text-decoration-none" data-offer-favorite="{{ $offer->getKey() }}"
    @if ($offer->getKey()) data-favorite-url="{{ route('website.favorites.store', $offer) }}" @endif
    data-favorite-login-url="{{ route('website.login') }}" data-favorite-authenticated="{{ auth('users')->check() ? 'true' : 'false' }}"
    data-add-label="{{ __('Add to favorites') }}" data-remove-label="{{ __('Remove from favorites') }}"
    data-add-success="{{ __('Added to favorites successfully') }}" data-remove-success="{{ __('Removed from favorites successfully') }}"
    aria-pressed="{{ $isFavorited ? 'true' : 'false' }}" aria-label="{{ $isFavorited ? __('Remove from favorites') : __('Add to favorites') }}">
    <x-icon :icon="$isFavorited ? 'ti ti-heart-filled' : 'ti ti-heart'" />
</button>
