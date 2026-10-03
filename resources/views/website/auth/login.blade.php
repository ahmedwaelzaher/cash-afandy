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
                    <h1 class="login-split__title mb-5">
                        {{ __('Login') }}
                    </h1>

                    <div class="mb-4">
                        <x-input type="email" name="email" :title="__('Email')" value="{{ old('email') }}"
                            validation="required|email" />
                    </div>

                    <div class="mb-3">
                        <x-input type="password" name="password" :title="__('Password')" validation="required" />
                    </div>

                    <div class="form-footer mb-3">
                        <button type="submit" class="btn btn-login-primary w-100">
                            {{ __('Continue') }}
                        </button>
                    </div>

                    <p class="text-center mb-4">
                        <a href="{{ route('website.password.request') }}" class="forgot-password-link">
                            {{ __('Forgot Password?') }}
                        </a>
                    </p>

                    <div class="hr-text mb-4">{{ __('OR') }}</div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <a href="{{ route('website.magic-link.create') }}" class="btn btn-social btn-social-magic-link">
                            {{ __('Login with Magic Link') }}
                        </a>

                        <x-social-login />
                    </div>

                    <p class="login-split__footer-text text-center mb-0">
                        {{ __('Haven\'t started your savings journey yet?') }}
                        <a href="{{ route('website.register') }}">{{ __('Start now') }}</a>
                    </p>
                </x-form>
            </div>
        </div>

        <div
            class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/login-illustration.svg') }}" alt=""
                class="login-split__illustration-img" />

            <div class="login-split__dots">
                <span></span>
                <span class="is-active"></span>
                <span></span>
            </div>
        </div>
    </div>
</x-layouts::website.auth-split>
