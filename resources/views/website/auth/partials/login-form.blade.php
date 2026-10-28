<div class="row g-0 align-items-center login-split">
    <div class="col-12 col-lg-6 login-split__form-col">
        <div class="login-split__form-wrap mx-auto">
            <x-form :action="route('website.login.store')" method="POST" :data-login-modal-form="$modal ?? false">
                @if ($modal ?? false)
                    <h2 class="login-split__title h1 mb-3" id="site-login-title">{{ __('Login') }}</h2>
                @else
                    <h1 class="login-split__title mb-3">{{ __('Login') }}</h1>
                @endif

                <div class="mb-3">
                    <x-input type="email" name="email" :title="__('Email')" value="{{ ($modal ?? false) ? '' : old('email') }}" autocomplete="email"
                        validation="required|email" />
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <x-label :title="__('Password')" :for="($modal ?? false) ? 'login-modal-password' : 'login-password'" required class="mb-0" />
                        <a href="{{ route('website.password.request') }}" class="forgot-password-link small">
                            {{ __('Forgot Password ?') }}
                        </a>
                    </div>
                    <x-input type="password" :id="($modal ?? false) ? 'login-modal-password' : 'login-password'" name="password" validation="required" autocomplete="current-password" />
                </div>

                <div class="form-footer mt-3">
                    <button type="submit" class="btn btn-brand w-100">
                        {{ __('Continue') }}
                    </button>
                </div>

                <div class="hr-text my-3">{{ __('OR') }}</div>

                <div class="d-flex flex-column gap-2 mb-3">
                    <a href="{{ route('website.magic-link.create') }}" class="btn btn-social flex-center btn-social-magic-link">
                        {{ __('Log in without a password') }}
                    </a>

                    <x-social-login :return-to="($modal ?? false) ? request()->fullUrl() : null" />
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
