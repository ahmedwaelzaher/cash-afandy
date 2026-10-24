@props([
    'error' => '500',
    'class' => 'd-flex flex-column',
    'illustration' => null,
])

{{-- Check if the current request belongs to the dashboard scope --}}
@php($isDashboard = is_dashboard_request() && current_admin() !== null)

<x-layouts::scaffold :title="$error" class="{{ $class }}{{ $illustration ? ' website-layout site-error-page' : '' }}">
    @if ($isDashboard)
        @themer('dashboard-theme')
    @else
        @themer('website-theme')
    @endif

    @if ($illustration)
        @pushOnce('styles')
            <link rel="stylesheet" href="{{ hashed_asset('assets/css/website.css') }}" />
        @endPushOnce

        <main class="page">
            <div class="container py-3">
                <a href="{{ $isDashboard ? route('dashboard.index') : route('website.index') }}" class="d-inline-block" aria-label="{{ __('Home') }}">
                    <img src="{{ hashed_asset('assets/images/logo-h.svg') }}" alt="{{ app_name() }}" width="180" height="80" class="object-fit-contain">
                </a>
            </div>
            <div class="container py-4 my-auto">
                <div class="site-error-content row align-items-center justify-content-center g-4">
                    <div class="col-12 col-md-6 text-center order-md-last">
                        <img src="{{ hashed_asset($illustration) }}" alt="" width="1024" height="1536" class="site-error-illustration object-fit-contain">
                    </div>
                    <div class="col-12 col-md-6 text-center text-md-start">
                        <div class="site-error-code fw-bold lh-1 mb-3" dir="ltr">{{ $error }}</div>
                        <h1 class="mb-3">@yield('title')</h1>
                        @hasSection('subtitle')
                            <p class="text-secondary fs-3 mb-4">@yield('subtitle')</p>
                        @endif
                        <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                            <a href="{{ $isDashboard ? route('dashboard.index') : route('website.index') }}" class="btn btn-primary gap-2">
                                <x-icon icon="ti ti-home" /> {{ __('Back to Home') }}
                            </a>
                            @if ($error === '500')
                                <button type="button" class="btn btn-outline-primary gap-2" onclick="window.location.reload()">
                                    <x-icon icon="ti ti-refresh" /> {{ __('Refresh') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @else
    <div class="page">
        <div class="container container-tight py-4 my-auto">
            <div class="empty">
                <div class="empty-header">
                    {{ $error }}
                </div>

                <p class="empty-title">
                    @yield('title')
                </p>

                @hasSection('subtitle')
                    <p class="empty-subtitle text-muted">
                        @yield('subtitle')
                    </p>
                @endif

                @hasSection('action')
                    <div class="empty-action">
                        @yield('action')
                    </div>
                @else
                    <div class="empty-action">
                        <a href="{{ $isDashboard ? route('dashboard.index') : route('website.index') }}" class="btn btn-primary">
                            <i class="fa fa-arrow-left me-3"></i>
                            {{ __('Back to Home') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</x-layouts::scaffold>
