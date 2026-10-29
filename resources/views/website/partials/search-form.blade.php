@php
    $headerSearch = $id === 'navbar-search';
    $searchCategory = in_array(request('afandy_category'), ['stores', 'offers'], true) ? request('afandy_category') : 'offers';
@endphp
<form action="{{ route('website.search') }}" method="GET" role="search" @class(['site-search position-relative d-flex align-items-center gap-2', 'p-1' => ! $headerSearch])
    @if ($headerSearch) data-afandy-search disable-validation @endif>
    <input type="hidden" name="afandy_category" value="{{ $searchCategory }}">
    @if ($headerSearch)
        <div class="dropdown site-search-type flex-shrink-0">
            <button type="button" class="btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Type') }}" aria-controls="{{ $id }}-types">
                <span data-search-type-label>{{ $searchCategory === 'stores' ? __('Stores') : __('Offers') }}</span>
                <x-icon icon="ti ti-chevron-down" class="m-0" />
            </button>
            <div class="dropdown-menu" id="{{ $id }}-types">
                @foreach (['offers' => ['ti ti-ticket', __('Offers')], 'stores' => ['ti ti-building-store', __('Stores')]] as $type => [$icon, $label])
                    <button type="button" @class(['dropdown-item gap-2', 'active' => $searchCategory === $type]) data-search-category="{{ $type }}" aria-pressed="{{ $searchCategory === $type ? 'true' : 'false' }}">
                        <x-icon :icon="$icon" />
                        <span data-search-category-label>{{ $label }}</span>
                        <x-icon icon="ti ti-check" :class="$searchCategory === $type ? 'ms-auto' : 'ms-auto d-none'" />
                    </button>
                @endforeach
            </div>
        </div>
    @endif
    <div class="site-search-field position-relative flex-fill">
        <x-input :id="$id" type="search" name="afandy_search" :value="is_string(request('afandy_search')) ? request('afandy_search') : ''" :aria-label="__('Search')" :placeholder="__('Type to search...')" maxlength="255" minlength="2" required validation="required"
            :autocomplete="$headerSearch ? 'off' : null" :role="$headerSearch ? 'combobox' : null" :aria-haspopup="$headerSearch ? 'dialog' : null" :aria-controls="$headerSearch ? 'afandy-search-results' : null" :aria-expanded="$headerSearch ? 'false' : null" />
        @if (! empty($submitLabel))
            <span class="input-icon-addon start-0 ms-3" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
        @else
            <button class="btn btn-icon btn-ghost-secondary position-absolute top-50 end-0 translate-middle-y m-0" type="submit" aria-label="{{ __('Search') }}"
                @if ($headerSearch) data-afandy-search-button data-search-label="{{ __('Search') }}" data-reset-label="{{ __('Reset') }}" @endif>
                <x-icon icon="ti ti-search" class="m-0" />
                <span class="d-flex" data-submit-spinner></span>
            </button>
        @endif
    </div>
    @if (! empty($submitLabel))
        <button class="btn btn-primary flex-shrink-0" type="submit">{{ $submitLabel }}</button>
    @endif
</form>
@if ($headerSearch)
    <div class="site-search-results" id="afandy-search-results" role="dialog" aria-label="{{ __('Search') }}" aria-busy="false" hidden></div>
@endif
