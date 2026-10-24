<x-layouts::website.auth-split :title="__('Forgot password')">
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <x-status class="login-split__status mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.password.email')" method="POST">
                    <h1 class="login-split__title mb-3">{{ __('Forgot password') }}</h1>

                    <p class="text-secondary mb-4">
                        {{ __('Enter your email address and your reset password link will be emailed to you.') }}
                    </p>

                    <div class="mb-4">
                        <x-input type="email" name="email" :title="__('Email address')" value="{{ old('email') }}"
                            placeholder="your@email.com" validation="required|email" />
                    </div>

                    <div class="form-footer mt-3 mb-4">
                        <button type="submit" class="btn btn-login-primary w-100">{{ __('Send password reset link') }}</button>
                    </div>

                    <p class="text-center mb-0">
                        <a href="{{ route('website.login') }}" class="forgot-password-link d-inline-flex align-items-center gap-2">
                            <x-icon icon="ti ti-arrow-right" class="site-menu-arrow" /> {{ __('Back to login') }}
                        </a>
                    </p>
                </x-form>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-forgot-password.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
