<x-form id="logout" :action="route('website.logout')" method="POST" class="d-none" disable-validation />

@php
    $localeFlags = ['en' => 'us', 'ar' => 'eg'];
    $currentCountry = website_country();
    $availableCountries = \App\Models\Country::whereIn('code', setting('website_countries'))->get();
    $navbarCategories = \App\Models\Category::all();
@endphp

<header class="site-navbar d-print-none">
    <div class="site-navbar-top">
        <div class="container justify-content-between d-flex align-items-center gap-3">
            <a class="navbar-brand my-2" href="{{ route('website.index') }}">
                <x-logo class="logo" />
            </a>

            <form action="{{ route('website.search') }}" method="GET" class="site-navbar-search input-icon flex-fill d-none d-lg-flex">
                <input type="search" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="{{ __('Search') }}..." />
                <span class="input-icon-addon">
                    <i class="fa fa-search"></i>
                </span>
            </form>

            <div class="d-none d-lg-flex align-items-center gap-2">
                @if ($currentCountry)
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="flag flag-country-{{ $currentCountry->code }}"></span>
                            {{ app()->getLocale() === 'ar' ? $currentCountry->native : $currentCountry->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach ($availableCountries as $country)
                                <a class="dropdown-item" href="{{ url()->current() }}?country={{ $country->code }}">
                                    <span class="flag flag-country-{{ $country->code }}"></span>
                                    {{ app()->getLocale() === 'ar' ? $country->native : $country->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count(setting('website_locales')) > 1)
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            @isset($localeFlags[app()->getLocale()])
                                <span class="flag flag-country-{{ $localeFlags[app()->getLocale()] }}"></span>
                            @endisset
                            {{ config('app.locales.' . app()->getLocale()) }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach (setting('website_locales') as $locale)
                                <a class="dropdown-item" href="{{ url()->current() }}?locale={{ $locale }}">
                                    @isset($localeFlags[$locale])
                                        <span class="flag flag-country-{{ $localeFlags[$locale] }}"></span>
                                    @endisset
                                    {{ config('app.locales.' . $locale) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <button class="navbar-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse"
                data-bs-target="#site-navbar-collapse">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </div>
</header>

<nav class="site-navbar-bottom navbar navbar-expand-lg">
    <div class="container">
        <div class="navbar-collapse collapse" id="site-navbar-collapse">
            <form action="{{ route('website.search') }}" method="GET" class="site-navbar-search input-icon d-flex d-lg-none my-3">
                <input type="search" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="{{ __('Search') }}..." />
                <span class="input-icon-addon">
                    <i class="fa fa-search"></i>
                </span>
            </form>

            <div
                class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-lg-center w-100 gap-2 gap-lg-3">
                <ul class="navbar-nav site-navbar-links">
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('website.coupons.*')) active @endif"
                            href="{{ route('website.coupons.index') }}">{{ __('Coupons') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('website.cashbacks.*')) active @endif"
                            href="{{ route('website.cashbacks.index') }}">{{ __('Cashback') }}</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if (request()->routeIs('website.categories.*')) active @endif"
                            href="#" data-bs-toggle="dropdown">
                            {{ __('Categories') }}
                        </a>

                        <div class="dropdown-menu">
                            @forelse ($navbarCategories as $category)
                                <a class="dropdown-item" href="{{ route('website.categories.show', $category) }}">
                                    {{ $category->title }}
                                </a>
                            @empty
                                <span class="dropdown-item-text text-secondary small">{{ __('Coming soon') }}</span>
                            @endforelse
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">{{ __('Articles') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">{{ __('News') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">{{ __('Offers') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">{{ __('About Us') }}</a>
                    </li>
                </ul>

                <div class="site-navbar-auth d-flex flex-column flex-lg-row gap-2 my-3 my-lg-0">
                    @auth('users')
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                                data-bs-toggle="dropdown">
                                <span class="nav-link-icon"><i class="fa fa-user"></i></span>
                                {{ current_user()->full_name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="{{ route('website.profile.edit') }}" class="dropdown-item">
                                    <span class="dropdown-item-title">{{ __('Profile') }}</span>
                                </a>

                                <a href="#" class="dropdown-item"
                                    onclick="event.preventDefault(); $('#logout').submit()">
                                    <span class="dropdown-item-title">{{ __('Logout') }}</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="site-navbar-cta d-flex gap-2">
                            <a href="{{ route('website.register') }}" class="btn btn-outline-brand brand-btn">
                                {{ __('Subscribe') }}
                            </a>

                            <a href="{{ route('website.login') }}" class="btn btn-brand brand-btn">
                                {{ __('Login') }}
                            </a>
                        </div>
                    @endauth

                    @if ($currentCountry)
                        <div class="dropdown d-flex d-lg-none align-items-center gap-3 mt-2">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="flag flag-country-{{ $currentCountry->code }}"></span>
                                {{ app()->getLocale() === 'ar' ? $currentCountry->native : $currentCountry->name }}
                            </a>

                            <div class="dropdown-menu">
                                @foreach ($availableCountries as $country)
                                    <a class="dropdown-item" href="{{ url()->current() }}?country={{ $country->code }}">
                                        <span class="flag flag-country-{{ $country->code }}"></span>
                                        {{ app()->getLocale() === 'ar' ? $country->native : $country->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</nav>
