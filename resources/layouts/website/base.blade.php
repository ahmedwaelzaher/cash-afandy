<x-layouts::scaffold :title="$title" :inline="$inline" {{ $attributes->class(['website-layout', $inline ? 'inline-layout' : 'standard-layout']) }}>
    @themer('website-theme')

    @pushOnce('meta')
        <meta name="mitgo-verification" content="b4e9bf6b-7c0b-4ca8-96d2-0a15e960a7fb" />
        <meta name="mitgo-verification" content="1373fcd4-9b3c-4b59-af2a-d4e4b4ebc9f1" />
    @endPushOnce

    {{-- Website styles aren't dark-mode ready yet, so force light regardless of system/localStorage preference --}}
    @push('pre-content')
        <script>document.documentElement.setAttribute('data-bs-theme', 'light');</script>
    @endpush

    @pushOnce('plugins-styles', 'tabler-flags-styles')
        <link rel="stylesheet" href="{{ hashed_asset('vendor/tabler/css/tabler-flags.min.css') }}" />
    @endPushOnce

    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/website.css') }}" />

        {!! setting('head_code') !!}
    @endPushOnce

    @if (setting('page_loader_enabled'))
        <x-page-loader variant="coin" />
    @endif

    <main class="page">
        @if (! $inline)
            @include('layouts.website.partials.navbar')
        @endif

        <div class="page-wrapper">
            <div class="page-body mt-0">
                {{ $slot }}
            </div>
        </div>

        @if (! $inline)
            @include('layouts.website.partials.footer')
        @endif
    </main>

    @if (! request()->routeIs('website.login'))
        @include('website.auth.partials.login-modal')
    @endif

    @include('components.google-analytics')
    @include('components.facebook-pixel')

    @pushOnce('scripts')
        <script src="{{ hashed_asset('assets/js/website.js') }}"></script>

        {!! setting('body_code') !!}
    @endPushOnce
</x-layouts::scaffold>
