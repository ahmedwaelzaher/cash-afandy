{{-- Use the existing themer actions and Tabler visibility utilities for instant switching. --}}
<button type="button" class="btn btn-icon btn-ghost-secondary hide-theme-dark" set-theme="dark" aria-label="{{ __('Dark mode') }}" title="{{ __('Dark mode') }}">
    <x-icon icon="ti ti-moon" />
</button>

<button type="button" class="btn btn-icon btn-ghost-secondary hide-theme-light" set-theme="light" aria-label="{{ __('Light mode') }}" title="{{ __('Light mode') }}">
    <x-icon icon="ti ti-sun" />
</button>
