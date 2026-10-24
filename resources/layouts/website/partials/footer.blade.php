<div class="border-top site-footer" style="background: var(--tblr-bg-surface)">
    <footer class="container py-5">
        <div class="row">
            <div class="col-12 col-lg-4 mb-4 mb-lg-0">
                <h3>{{ __('About :app', ['app' => app()->isLocale('ar') ? 'الأفندي' : 'Afandy']) }}</h3>

                <div class="text-body-secondary mb-0">
                    {!! Arr::get(setting('footer_about_description'), app()->getLocale()) !!}
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 mb-4 mb-lg-0">
                <h3>{{ __('Quick Links') }}</h3>

                <ul class="nav flex-column">
                    @foreach (\App\Models\StaticPage::whereIn('slug', ['privacy-policy', 'terms-and-conditions'])->select('title', 'slug')->orderBy('id')->get() as $page)
                        <li class="nav-item mb-2">
                            <a href="{{ route('website.static-pages.show', $page->slug) }}"
                                class="nav-link p-0 text-body-secondary">
                                {{ $page->title }}
                            </a>
                        </li>
                    @endforeach
                    <li class="nav-item mb-2">
                        <a href="mailto:support@cashafady.com" class="nav-link p-0 text-body-secondary">{{ __('Reach Out') }}</a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <x-form route="website.subscribers.store" method="POST" toast-errors>
                    <h3>{{ __('Subscribe Now') }}</h3>

                    <div class="site-footer-subscribe input-group mb-3">
                        <x-input type="email" name="email" :placeholder="__('Email Address')" validation="required" />
                        <button class="btn btn-brand" type="submit">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>

                    <x-radios name="gender" :options="\App\Enums\Gender::values()"
                        :value="\App\Enums\Gender::Male->value" inline />

                    <div class="text-body-secondary small mt-3 mb-0">
                        {!! Arr::get(setting('footer_subscribe_description'), app()->getLocale()) !!}
                    </div>
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

                    <li class="site-footer-social-item flex-center rounded-circle">
                        <a class="site-footer-social d-inline-flex align-items-center justify-content-center rounded-circle border-0 bg-transparent" href="{{ setting($key) }}" target="_blank" rel="noopener noreferrer">
                            <i class="fab {{ $icon }}"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </footer>
</div>
