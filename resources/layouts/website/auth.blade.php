<x-layouts::website {{ $attributes->class(['auth-page']) }}>
    <div class="site-auth container container-tight d-flex flex-column justify-content-center py-3">
        {{ $slot }}
    </div>
</x-layouts::website>
