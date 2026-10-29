@php
    $headerSearch = $id === 'navbar-search';
    $searchCategory = in_array(request('afandy_category'), ['stores', 'offers'], true) ? request('afandy_category') : 'offers';
@endphp
<form action="{{ route('website.search') }}" method="GET" role="search" class="site-search position-relative d-flex align-items-center gap-2 p-1"
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
    <div class="site-search-field d-flex align-items-center gap-2 flex-fill">
        @if (! empty($submitLabel))
            <span class="d-flex align-items-center flex-shrink-0 text-secondary" aria-hidden="true"><x-icon icon="ti ti-search" class="m-0" /></span>
        @endif
        <x-input :id="$id" type="search" name="afandy_search" :value="is_string(request('afandy_search')) ? request('afandy_search') : ''" :aria-label="__('Search')" :placeholder="__('Type to search...')" maxlength="255" minlength="2" required validation="required"
            class="px-0" :autocomplete="$headerSearch ? 'off' : null" :role="$headerSearch ? 'combobox' : null" :aria-haspopup="$headerSearch ? 'dialog' : null" :aria-controls="$headerSearch ? 'afandy-search-results' : null" :aria-expanded="$headerSearch ? 'false' : null" />
    </div>
    <button @class(['btn flex-shrink-0 align-self-stretch', 'btn-primary' => ! empty($submitLabel), 'btn-icon btn-ghost-secondary' => empty($submitLabel)]) type="submit" aria-label="{{ $submitLabel ?? __('Search') }}"
        @if ($headerSearch) data-afandy-search-button data-search-label="{{ __('Search') }}" data-reset-label="{{ __('Reset') }}" @endif>
        @if (! empty($submitLabel))
            {{ $submitLabel }}
        @else
            <x-icon icon="ti ti-search" class="m-0" />
        @endif
        <span class="d-flex" data-submit-spinner></span>
    </button>
</form>
@if ($headerSearch)
    <div class="site-search-results" id="afandy-search-results" role="dialog" aria-label="{{ __('Search') }}" aria-busy="false" hidden></div>
@endif
