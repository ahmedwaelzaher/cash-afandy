<x-layouts::website {{ $attributes->class(['auth-page']) }}>
    @pushOnce('styles', 'auth-split-styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <div class="site-auth container d-flex flex-column justify-content-center py-3">
        {{ $slot }}
    </div>
</x-layouts::website>
