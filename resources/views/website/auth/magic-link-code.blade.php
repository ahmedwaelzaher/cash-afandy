<x-layouts::website.auth-split :title="__('Enter Code')">
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <x-status class="login-split__status mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.magic-link-code.store')" method="POST">
                    <h1 class="login-split__title mb-3">{{ __('Enter Code') }}</h1>

                    <p class="text-secondary mb-4">{{ __('We sent a code to :email. Enter it below or click the link in the email.', ['email' => $email]) }}</p>

                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-3">
                        <x-input type="text" name="code" :title="__('Code')" :placeholder="__('Enter 6-digit code')"
                            validation="required" maxlength="6" autocomplete="one-time-code" inputmode="numeric" />
                    </div>

                    <div class="form-footer mt-3 mb-4">
                        <button type="submit" class="btn btn-login-primary w-100">{{ __('Verify Code') }}</button>
                    </div>

                    <p class="text-center mb-0">
                        <a href="{{ route('website.magic-link.create') }}" class="forgot-password-link">{{ __('Didn\'t receive the email? Try again') }}</a>
                    </p>
                </x-form>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-magic-link.webp') }}" alt="" width="1024" height="1536" class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
