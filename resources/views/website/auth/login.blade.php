<x-layouts::website.auth-split :title="__('Login')">
    @pushOnce('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
            rel="stylesheet" />

        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <x-status class="login-split__status mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.login.store')" method="POST">
                    <h1 class="login-split__title mb-3">
                        {{ __('Login') }}
                    </h1>

                    <div class="mb-3">
                        <x-input type="email" name="email" :title="__('Email')" value="{{ old('email') }}"
                            validation="required|email" />
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <x-label :title="__('Password')" for="login-password" required class="mb-0" />
                            <a href="{{ route('website.password.request') }}" class="forgot-password-link small">
                                {{ __('Forgot Password ?') }}
                            </a>
                        </div>
                        <x-input type="password" id="login-password" name="password" validation="required" />
                    </div>

                    <div class="form-footer mt-3 mb-3">
                        <button type="submit" class="btn btn-login-primary w-100">
                            {{ __('Continue') }}
                        </button>
                    </div>

                    <div class="hr-text mb-3">{{ __('OR') }}</div>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <a href="{{ route('website.magic-link.create') }}" class="btn btn-social flex-center btn-social-magic-link">
                            {{ __('Log in without a password') }}
                        </a>

                        <x-social-login />
                    </div>

                    <p class="login-split__footer-text text-center mb-0">
                        {{ __("Haven't started your savings journey yet ?") }}
                        <a href="{{ route('website.register') }}">{{ __('Start now') }}</a>
                    </p>
                </x-form>
            </div>
        </div>

        <div
            class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-login.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
