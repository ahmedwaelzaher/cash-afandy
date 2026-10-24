<x-layouts::website {{ $attributes->class(['auth-page', 'login-page']) }}>
    <div class="site-auth container d-flex flex-column justify-content-center py-3">
        {{ $slot }}
    </div>
</x-layouts::website>
