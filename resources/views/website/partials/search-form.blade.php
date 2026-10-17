<form action="{{ route('website.search') }}" method="GET" role="search" class="site-search position-relative p-1">
    <x-input :id="$id" type="search" name="search" :value="request('search')" :aria-label="__('Search for a store or offer')" :placeholder="__('Store or offer name')" required validation="required" />
    <button class="btn btn-icon btn-ghost-secondary position-absolute top-0 start-0 m-1" type="submit" aria-label="{{ __('Search') }}"><x-icon icon="ti ti-search" /></button>
</form>
