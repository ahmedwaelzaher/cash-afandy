@props([
    'lazy' => true,
    'variant' => 'vertical',
])

@if ($variant === 'horizontal')
    {{-- The mascot leads the reading direction: on the right in RTL, on the left in LTR. --}}
    <img src="{{ hashed_asset(\Redot\Models\Language::current()->direction === 'rtl' ? 'assets/images/logo-h.svg' : 'assets/images/logo-h-ltr.svg') }}"
        alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}" {{ $attributes }} />
@else
    <img src="{{ setting('app_logo_dark') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
        {{ $attributes->class(['hide-theme-light']) }} />
    <img src="{{ setting('app_logo_light') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
        {{ $attributes->class(['hide-theme-dark']) }} />
@endif
