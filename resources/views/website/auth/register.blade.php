<x-layouts::website.auth :title="__('Create new account')">
    <x-status />

    <x-form class="card card-md" :action="route('website.register.store')" method="POST">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="h2 mb-2">
                    {{ __('Start your journey') }}
                </h2>
            </div>

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

            @include('website.profile.partials.personal-details')

            <div class="row align-items-center">
                <div class="col-12 col-md-6 mb-3">
                    <span class="form-label mb-0">{{ __('Do you have a code?') }}</span>
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <x-toggle id="has-referral-code" :on="__('Yes, I have an invite code')" :off="__('No, I don\'t have an invite code')" />
                </div>
            </div>

            <div class="mb-3" visible-when="$has-referral-code">
                <x-input type="text" name="referral_code" :title="__('Invite Code')" value="{{ old('referral_code') }}"
                    :placeholder="__('Invite Code')" />
            </div>

            @if (setting('cloudflare_turnstile_site_key'))
                <x-captcha :title="__('Captcha')" name="captcha" />
            @endif

            <div class="form-footer">
                <button type="submit" class="btn btn-brand w-100">{{ __('Continue') }}</button>
            </div>

            <x-social-login divider />

            <p class="text-muted text-center mt-4 mb-0">
                {{ __('Already started your savings journey?') }}
                <a href="{{ route('website.login') }}">{{ __('Login now') }}</a>
            </p>
        </div>
    </x-form>
</x-layouts::website.auth>
