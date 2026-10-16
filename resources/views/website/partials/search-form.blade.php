<form action="{{ route('website.search') }}" method="GET" role="search" class="site-search">
    <x-input :id="$id" type="search" name="search" :value="request('search')" :aria-label="__('Search for a store or offer')" :placeholder="__('Store or offer name')" required validation="required" />
    <button class="btn btn-icon" type="submit" aria-label="{{ __('Search') }}"><x-icon icon="fa-solid fa-magnifying-glass" /></button>
</form>
