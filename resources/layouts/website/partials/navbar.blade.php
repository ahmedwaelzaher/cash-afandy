@php
    $currentCountry = website_country();
    $availableCountries = \App\Models\Country::whereIn('code', setting('website_countries'))->get();
    $otherLocales = array_values(array_diff(setting('website_locales'), [app()->getLocale()]));
    $navbarCategories = \App\Models\Category::all();
@endphp

<header class="site-navbar d-print-none">
    <div class="container site-header">
        <a class="navbar-brand" href="{{ route('website.index') }}"><x-logo class="logo" variant="horizontal" :lazy="false" /></a>
        <div class="site-header-search" id="site-header-search">
            @include('website.partials.search-form', ['id' => 'navbar-search'])
        </div>
        <div class="site-header-actions">
            @auth('users')
                <div class="dropdown">
                    <button type="button" class="btn btn-brand site-account-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Profile') }}"><x-icon icon="fa-regular fa-user" /><span>{{ __('Profile') }}</span></button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <span class="dropdown-header text-break">{{ current_user()->full_name }}</span>
                        <a href="{{ route('website.profile.edit') }}" class="dropdown-item">{{ __('Profile') }}</a>
                        <x-form :action="route('website.logout')" method="POST" disable-validation>
                            <button type="submit" class="dropdown-item">{{ __('Logout') }}</button>
                        </x-form>
                    </div>
                </div>
            @else
                <a href="{{ route('website.login') }}" class="btn btn-brand site-account-button" aria-label="{{ __('Login') }}"><x-icon icon="fa-regular fa-user" /><span>{{ __('Login') }}</span></a>
            @endauth
            <button class="btn btn-icon d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#site-navbar-menu" aria-controls="site-navbar-menu" aria-expanded="false" aria-label="{{ __('Menu') }}"><x-icon icon="fa-solid fa-bars" /></button>
        </div>
        <div class="offcanvas-xl offcanvas-start site-navbar-menu" tabindex="-1" id="site-navbar-menu" aria-labelledby="site-navbar-title">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h3 mb-0" id="site-navbar-title">{{ __('Menu') }}</h2>
                <button type="button" class="btn btn-icon" data-bs-dismiss="offcanvas" data-bs-target="#site-navbar-menu" aria-label="{{ __('Close') }}"><x-icon icon="fa-solid fa-xmark" /></button>
            </div>
            <div class="offcanvas-body">
                <nav class="site-navbar-links" aria-label="{{ __('Main navigation') }}">
                    <a @class(['nav-link', 'active' => request()->routeIs('website.coupons.*')]) href="{{ route('website.coupons.index') }}" @if (request()->routeIs('website.coupons.*')) aria-current="page" @endif>{{ __('Coupons') }}</a>
                    <a @class(['nav-link', 'active' => request()->routeIs('website.cashbacks.*')]) href="{{ route('website.cashbacks.index') }}" @if (request()->routeIs('website.cashbacks.*')) aria-current="page" @endif>{{ __('Cashback') }}</a>
                    @if ($navbarCategories->isNotEmpty())
                        <div class="dropdown">
                            <button @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('website.categories.*')]) type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ __('Categories') }}</button>
                            <div class="dropdown-menu">
                                @foreach ($navbarCategories as $category)
                                    <a class="dropdown-item" href="{{ route('website.categories.show', $category) }}">{{ $category->title }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </nav>
                <div class="site-navbar-settings">
                    @if ($currentCountry && setting('show_website_countries_dropdown'))
                        <div class="dropdown">
                            <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Country') }}"><span class="site-setting-label d-xl-none">{{ __('Country') }}</span><x-flag :code="$currentCountry->code" size="sm" /> {{ app()->getLocale() === 'ar' ? $currentCountry->native : $currentCountry->name }}</button>
                            <div class="dropdown-menu">
                                @foreach ($availableCountries as $country)
                                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['country' => $country->code]) }}"><x-flag :code="$country->code" size="sm" /> {{ app()->getLocale() === 'ar' ? $country->native : $country->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (count($otherLocales) === 1)
                        <a class="nav-link site-language-link" href="{{ request()->fullUrlWithQuery(['locale' => $otherLocales[0]]) }}" lang="{{ $otherLocales[0] }}" aria-label="{{ __('Language') }}: {{ config('app.locales.' . $otherLocales[0]) }}"><span class="site-setting-label d-xl-none">{{ __('Language') }}</span>{{ config('app.locales.' . $otherLocales[0]) }}</a>
                    @elseif (count($otherLocales) > 1)
                        <div class="dropdown">
                            <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ config('app.locales.' . app()->getLocale()) }}</button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($otherLocales as $locale)
                                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}" lang="{{ $locale }}">{{ config('app.locales.' . $locale) }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
