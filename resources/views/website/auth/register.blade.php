<x-layouts::website.auth-split :title="__('Create new account')">
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <x-status class="login-split__status mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.register.store')" method="POST">
                    <h1 class="login-split__title mb-3">{{ __('Start your journey') }}</h1>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <x-input type="text" name="first_name" :title="__('First name')" value="{{ old('first_name') }}"
                                :placeholder="__('First name')" validation="required" />
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <x-input type="text" name="last_name" :title="__('Last name')" value="{{ old('last_name') }}"
                                :placeholder="__('Last name')" validation="required" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-input type="email" name="email" :title="__('Email address')" value="{{ old('email') }}"
                            placeholder="your@email.com" validation="required|email" />
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <x-input type="password" name="password" :title="__('Password')" :placeholder="__('Password')" validation="required" />
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <x-input type="password" name="password_confirmation" :title="__('Confirm Password')" :placeholder="__('Confirm Password')"
                                validation="required" />
                        </div>
                    </div>

                    @include('website.profile.partials.personal-details', ['showPhone' => false, 'showBirthdate' => false, 'requireGender' => true])

                    @if (setting('cloudflare_turnstile_site_key'))
                        <x-captcha :title="__('Captcha')" name="captcha" />
                    @endif

                    <div class="form-footer mt-3">
                        <button type="submit" class="btn btn-login-primary w-100">{{ __('Continue') }}</button>
                    </div>

                    <x-social-login divider />

                    <p class="text-muted text-center mt-4 mb-0">
                        {{ __('Already started your savings journey ?') }}
                        <a href="{{ route('website.login') }}">{{ __('Login now') }}</a>
                    </p>
                </x-form>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-register.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
