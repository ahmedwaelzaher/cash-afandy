<x-layouts::website.auth :title="__('Log in without a password')">
    <x-status />

    <x-form class="card card-md" :action="route('website.magic-link.store')" method="POST">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="h2 mb-2">
                    {{ __('Log in without a password') }}
                </h2>

                <p class="text-muted">
                    {{ __('Enter your email address and we will send you a login link.') }}
                </p>
            </div>

            <div class="mb-3">
                <x-input type="email" name="email" :title="__('Email address')" value="{{ old('email') }}"
                    placeholder="your@email.com" validation="required|email" />
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-brand w-100">{{ __('Send Magic Link') }}</button>
            </div>

            <p class="text-muted text-center mt-3 mb-0">
                <a href="{{ route('website.login') }}">{{ __('Back to login') }}</a>
            </p>
        </div>
    </x-form>
</x-layouts::website.auth>
