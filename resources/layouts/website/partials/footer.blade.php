<div class="border-top site-footer" style="background: var(--tblr-bg-surface)">
    <footer class="container py-5">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4 mb-4 mb-lg-0">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ hashed_asset('assets/images/mascot.svg') }}" alt="{{ app_name() }}"
                        class="site-footer-mascot flex-shrink-0" loading="lazy" />

                    <div>
                        <h3>{{ __('About :app', ['app' => app_name()]) }}</h2>

                        <p class="text-body-secondary mb-0">
                            {{  Arr::get(setting('footer_about_description'), app()->getLocale()) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h3>{{ __('Our Services') }}</h2>

                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link p-0 text-body-secondary">{{ __('All Categories') }}</a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link p-0 text-body-secondary">{{ __('Coupons') }}</a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link p-0 text-body-secondary">{{ __('Cashback') }}</a>
                    </li>

                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link p-0 text-body-secondary">{{ __('About Us') }}</a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h3>{{ __('Important Links') }}</h2>

                <ul class="nav flex-column">
                    @foreach (\App\Models\StaticPage::select('title', 'slug')->get() as $page)
                        <li class="nav-item mb-2">
                            <a href="{{ route('website.static-pages.show', $page->slug) }}"
                                class="nav-link p-0 text-body-secondary">
                                {{ $page->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="col-12 col-lg-4">
                <x-form route="website.subscribers.store" method="POST" toast-errors>
                    <h3>{{ __('Subscribe Now') }}</h2>

                    <div class="site-footer-subscribe input-group mb-3">
                        <x-input type="email" name="email" :placeholder="__('Email Address')" validation="required" />
                        <button class="btn btn-brand" type="submit">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>

                    <x-radios name="gender" :options="\App\Enums\Gender::values()"
                        :value="\App\Enums\Gender::Male->value" inline />

                    <p class="text-body-secondary small mt-3 mb-0">
                        {{ Arr::get(setting('footer_subscribe_description'), app()->getLocale()) }}
                    </p>
                </x-form>
            </div>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center text-center pt-4 mt-4 border-top gap-3">
            <p class="mb-0">
                &copy; {{ date('Y') }}
                {{ __('All rights reserved for') }}
                <a href="{{ url('/') }}">{{ app_name() }}</a>
            </p>

            @php
                $socialLinks = [
                    'social_facebook' => 'fa-facebook-f',
                    'social_instagram' => 'fa-instagram',
                    'social_whatsapp' => 'fa-whatsapp',
                    'social_x' => 'fa-x-twitter',
                    'social_telegram' => 'fa-telegram',
                    'social_tiktok' => 'fa-tiktok',
                ];
            @endphp

            <ul class="site-footer-social-list list-unstyled d-flex justify-content-center mb-0">
                @foreach ($socialLinks as $key => $icon)
                    @continue(! setting($key))

                    <li class="site-footer-social-item">
                        <a class="site-footer-social" href="{{ setting($key) }}" target="_blank" rel="noopener noreferrer">
                            <i class="fab {{ $icon }}"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </footer>
</div>
