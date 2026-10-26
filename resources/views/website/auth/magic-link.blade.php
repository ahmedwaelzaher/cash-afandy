<x-layouts::website.auth-split :title="__('Log in without a password')">
    <x-status class="mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.magic-link.store')" method="POST">
                    <h1 class="login-split__title mb-3">{{ __('Log in without a password') }}</h1>

                    <p class="text-secondary mb-4">{{ __('Enter your email address and we will send you a login link.') }}</p>

                    <div class="mb-3">
                        <x-input type="email" name="email" :title="__('Email address')" value="{{ old('email') }}"
                            placeholder="your@email.com" validation="required|email" />
                    </div>

                    <div class="form-footer mt-3 mb-4">
                        <button type="submit" class="btn btn-brand w-100">{{ __('Send Magic Link') }}</button>
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
            <img src="{{ hashed_asset('assets/images/afandy-magic-link.webp') }}" alt="" width="1024" height="1536" class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
