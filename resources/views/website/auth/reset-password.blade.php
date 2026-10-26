<x-layouts::website.auth-split :title="__('Reset password')">
    <x-status class="mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.password.store')" method="POST">
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <h1 class="login-split__title mb-3">{{ __('Reset password') }}</h1>

                    <p class="text-secondary mb-4">
                        {{ __('Make sure to use a strong password to keep your account secure.') }}
                    </p>

                    <div class="mb-3">
                        <x-input type="email" name="email" :title="__('Email address')" value="{{ old('email', $request->email) }}"
                            placeholder="your@email.com" validation="required|email" />
                    </div>

                    <div class="mb-3">
                        <x-input type="password" name="password" :title="__('Password')" placeholder="{{ __('Password') }}"
                            validation="required" />
                    </div>

                    <div class="mb-3">
                        <x-input type="password" name="password_confirmation" :title="__('Confirm Password')"
                            placeholder="{{ __('Confirm Password') }}" validation="required" />
                    </div>

                    <div class="form-footer mt-3">
                        <button type="submit" class="btn btn-brand w-100">{{ __('Reset password') }}</button>
                    </div>
                </x-form>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-password-unlocked.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
