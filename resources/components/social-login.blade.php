@props([
    // Render the buttons in their own wrapper, preceded by an "OR" divider.
    'divider' => false,
    'returnTo' => null,
])

@php
    $providers = collect([
        'google' => __('Sign in with Google'),
        'facebook' => __('Sign in with Facebook'),
    ])->filter(fn ($label, $provider) => \App\Http\Controllers\Website\SocialiteController::enabled($provider));
@endphp

@if ($providers->isNotEmpty())
    @if ($divider)
        <div class="hr-text my-3">{{ __('OR') }}</div>

        <div {{ $attributes->class(['d-flex flex-column gap-3']) }}>
    @endif

    @foreach ($providers as $provider => $label)
        <a href="{{ route('website.socialite.redirect', ['provider' => $provider, 'return_to' => $returnTo]) }}"
            @if ($returnTo) data-login-return @endif class="btn btn-social flex-center btn-social-{{ $provider }}">
            <x-social-icon :social="$provider" size="xs" />
            {{ $label }}
        </a>
    @endforeach

    @if ($divider)
        </div>
    @endif
@endif
