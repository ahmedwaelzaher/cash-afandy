@php
    $currentCountry = website_country();
    $availableCountries = \App\Models\Country::whereIn('code', setting('website_countries'))->get();
    $otherLocales = array_values(array_diff(setting('website_locales'), [app()->getLocale()]));
    $navbarCategories = \App\Models\Category::all();
@endphp

<header class="site-navbar sticky-top d-print-none">
    <div class="container site-header">
        <a class="navbar-brand m-0 p-0" href="{{ route('website.index') }}">
            <x-logo class="logo object-fit-contain" variant="horizontal" :lazy="false" />
        </a>

        <div class="site-header-search" id="site-header-search">
            @include('website.partials.search-form', ['id' => 'navbar-search'])
        </div>

        <div class="site-header-actions d-flex d-xl-none gap-2">
            @include('layouts.website.partials.account-menu', ['compact' => true])
            <button type="button" class="btn btn-icon btn-ghost-secondary" data-bs-toggle="offcanvas" data-bs-target="#site-navbar-menu"
                aria-controls="site-navbar-menu" aria-expanded="false" aria-label="{{ __('Menu') }}">
                <x-icon icon="ti ti-menu-2" />
            </button>
        </div>

        <div class="offcanvas-xl offcanvas-start site-navbar-menu" id="site-navbar-menu" tabindex="-1" aria-labelledby="site-navbar-title">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h3 mb-0" id="site-navbar-title">{{ __('Menu') }}</h2>
                <button type="button" class="btn btn-icon btn-ghost-secondary ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#site-navbar-menu" aria-label="{{ __('Close') }}">
                    <x-icon icon="ti ti-x" />
                </button>
            </div>

            <div class="offcanvas-body">
                <nav class="site-navbar-links d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center" aria-label="{{ __('Main navigation') }}">
                    @foreach (['coupons' => __('Coupons'), 'cashbacks' => __('Cashback')] as $resource => $label)
                        <a href="{{ route('website.' . $resource . '.index') }}"
                            @class(['nav-link', 'active' => request()->routeIs('website.' . $resource . '.*')])
                            @if (request()->routeIs('website.' . $resource . '.*')) aria-current="page" @endif>
                            <x-icon :icon="$resource === 'coupons' ? 'ti ti-ticket' : 'ti ti-percentage'" class="d-xl-none" />
                            {{ $label }}
                            <x-icon icon="ti ti-chevron-left" class="site-menu-arrow d-xl-none" />
                        </a>
                    @endforeach

                    @if ($navbarCategories->isNotEmpty())
                        <div class="dropdown">
                            <button type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('website.categories.*')])>
                                <x-icon icon="ti ti-layout-grid" class="d-xl-none" />
                                {{ __('Categories') }}
                                <x-icon icon="ti ti-chevron-left" class="site-menu-arrow d-xl-none" />
                            </button>
                            <div class="dropdown-menu">
                                @foreach ($navbarCategories as $category)
                                    <a href="{{ route('website.categories.show', $category) }}"
                                        @class(['dropdown-item', 'active' => request()->routeIs('website.categories.*') && request()->route('category')?->is($category)])>{{ $category->title }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </nav>

                <div class="site-navbar-settings d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center">
                    @if (($currentCountry && setting('show_website_countries_dropdown')) || count($otherLocales))
                        <p class="d-xl-none mb-1 fw-medium text-secondary">{{ __('Preferences') }}</p>
                    @endif
                    @if ($currentCountry && setting('show_website_countries_dropdown'))
                        <div class="dropdown site-preference-dropdown">
                            <button type="button" class="nav-link dropdown-toggle site-preference" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Country') }}">
                                <x-icon icon="ti ti-world" class="d-xl-none" />
                                <span class="d-flex flex-column align-items-start gap-1">
                                    <small class="d-xl-none">{{ __('Country') }}</small>
                                    <span class="d-flex align-items-center gap-2">
                                        <x-flag :code="$currentCountry->code" size="sm" />
                                        <span>{{ app()->getLocale() === 'ar' ? $currentCountry->native : $currentCountry->name }}</span>
                                    </span>
                                </span>
                                <x-icon icon="ti ti-chevron-left" class="site-menu-arrow d-xl-none" />
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($availableCountries as $country)
                                    <a href="{{ request()->fullUrlWithQuery(['country' => $country->code]) }}"
                                        @class(['dropdown-item', 'active' => $currentCountry->is($country)])
                                        @if ($currentCountry->is($country)) aria-current="true" @endif>
                                        <x-flag :code="$country->code" size="sm" />
                                        {{ app()->getLocale() === 'ar' ? $country->native : $country->name }}
                                        @if ($currentCountry->is($country))
                                            <x-icon icon="ti ti-check" class="ms-auto" />
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($otherLocales) === 1)
                        <a class="nav-link d-none d-xl-flex" href="{{ request()->fullUrlWithQuery(['locale' => $otherLocales[0]]) }}" lang="{{ $otherLocales[0] }}"
                            aria-label="{{ __('Language') }}: {{ config('app.locales.' . $otherLocales[0]) }}">
                            {{ config('app.locales.' . $otherLocales[0]) }}
                        </a>
                    @endif
                    @if (count($otherLocales))
                        <div @class(['dropdown site-preference-dropdown', 'd-xl-none' => count($otherLocales) === 1])>
                            <button type="button" class="nav-link dropdown-toggle site-preference" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Language') }}">
                                <x-icon icon="ti ti-language" class="d-xl-none" />
                                <span class="d-flex flex-column align-items-start gap-1">
                                    <small class="d-xl-none">{{ __('Language') }}</small>
                                    <span>{{ config('app.locales.' . app()->getLocale()) }}</span>
                                </span>
                                <x-icon icon="ti ti-chevron-left" class="site-menu-arrow d-xl-none" />
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach (setting('website_locales') as $locale)
                                    <a @class(['dropdown-item', 'active' => app()->getLocale() === $locale])
                                        href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}" lang="{{ $locale }}"
                                        @if (app()->getLocale() === $locale) aria-current="true" @endif>
                                        {{ config('app.locales.' . $locale) }}
                                        @if (app()->getLocale() === $locale)
                                            <x-icon icon="ti ti-check" class="ms-auto" />
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="d-none d-xl-block">
                        @include('layouts.website.partials.account-menu', ['compact' => false])
                    </div>
                </div>

                <div class="site-offcanvas-account d-xl-none">
                    @include('layouts.website.partials.account-menu', ['compact' => false])
                </div>
            </div>
        </div>
    </div>
</header>
