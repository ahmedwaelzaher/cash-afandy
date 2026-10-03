@props([
    'lazy' => true,
    'variant' => 'vertical',
])

@if ($variant === 'horizontal')
    <img src="{{ hashed_asset('assets/images/logo-h.svg') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
        {{ $attributes }} />
@else
    <img src="{{ setting('app_logo_dark') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
        {{ $attributes->class(['hide-theme-light']) }} />
    <img src="{{ setting('app_logo_light') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
        {{ $attributes->class(['hide-theme-dark']) }} />
@endif
