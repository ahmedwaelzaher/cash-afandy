@php($headerSearch = $id === 'navbar-search')
<form action="{{ route('website.search') }}" method="GET" role="search" class="site-search position-relative d-flex align-items-center gap-2 p-1"
    @if ($headerSearch) data-afandy-search disable-validation @endif>
    <x-input :id="$id" type="search" name="afandy_search" :value="is_string(request('afandy_search')) ? request('afandy_search') : ''" :aria-label="__('Search for products and offers')" :placeholder="__('Search for products and offers')" :class="empty($submitLabel) ? 'pe-5' : null" maxlength="255" minlength="2" required validation="required"
        :autocomplete="$headerSearch ? 'off' : null" :role="$headerSearch ? 'combobox' : null" :aria-haspopup="$headerSearch ? 'dialog' : null" :aria-controls="$headerSearch ? 'afandy-search-results' : null" :aria-expanded="$headerSearch ? 'false' : null" />
    @if (! empty($submitLabel))
        <span class="input-icon-addon start-0 ms-3" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
        <button class="btn btn-primary flex-shrink-0" type="submit">{{ $submitLabel }}</button>
    @else
        <button class="btn btn-icon btn-ghost-secondary position-absolute top-0 start-0 m-1" type="submit" aria-label="{{ __('Search') }}"
            @if ($headerSearch) data-afandy-search-button data-search-label="{{ __('Search') }}" data-reset-label="{{ __('Reset') }}" @endif><x-icon icon="ti ti-search" /></button>
        <span class="position-absolute top-50 end-0 translate-middle-y me-3 d-flex" data-submit-spinner></span>
    @endif
</form>
@if ($headerSearch)
    <div class="site-search-results" id="afandy-search-results" role="dialog" aria-label="{{ __('Search') }}" aria-busy="false" hidden></div>
@endif
