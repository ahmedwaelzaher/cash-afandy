<x-select :id="$id" :value="$value" {{ $attributes }}>
    {{ $slot }}
</x-select>

@if ($create)
    <a href="{{ route($create, $createParams) }}" class="btn btn-icon mt-2" data-lightbox data-type="iframe" aria-label="{{ __('Create') }}"><x-icon icon="ti ti-plus" /></a>
@endif
