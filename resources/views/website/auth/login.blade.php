<x-layouts::website.auth-split :title="__('Login')">
    <x-status class="mb-3" />

    @include('website.auth.partials.login-form')
</x-layouts::website.auth-split>
