<form action="{{ route('website.search') }}" method="GET" role="search" class="site-search position-relative d-flex align-items-center gap-2 p-1">
    <x-input :id="$id" type="search" name="search" :value="request('search')" :aria-label="__('Search for products and offers')" :placeholder="__('Search for products and offers')" :class="empty($submitLabel) ? 'pe-5' : null" required validation="required" />
    @if (! empty($submitLabel))
        <span class="input-icon-addon start-0 ms-3" aria-hidden="true"><x-icon icon="ti ti-search" /></span>
        <button class="btn btn-primary flex-shrink-0" type="submit">{{ $submitLabel }}</button>
    @else
        <button class="btn btn-icon btn-ghost-secondary position-absolute top-0 start-0 m-1" type="submit" aria-label="{{ __('Search') }}"><x-icon icon="ti ti-search" /></button>
        <span class="position-absolute top-50 end-0 translate-middle-y me-3 d-flex" data-submit-spinner></span>
    @endif
</form>
