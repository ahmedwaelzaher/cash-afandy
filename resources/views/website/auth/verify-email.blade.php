<x-layouts::website.auth-split :title="__('Verify your email address')">
    <x-status class="mb-3" />

    <x-form id="resend-verification" class="d-none" :action="route('website.verification.send')" method="POST" disable-validation />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <h1 class="login-split__title mb-3">{{ __('Check your inbox') }}</h1>

                <p class="text-secondary mb-4">
                    {{ __('We have sent you an email with a link to verify your email address and complete your registration.') }}
                </p>

                <p class="text-secondary mb-0">
                    {{ __('If you did not receive the email') }},
                    <a href="#" class="forgot-password-link" onclick="event.preventDefault(); $('#resend-verification').submit();">
                        {{ __('click here to request another') }}
                    </a>
                    .
                </p>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-verify-email.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
