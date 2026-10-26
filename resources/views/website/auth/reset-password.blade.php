<x-layouts::website.auth :title="__('Reset password')">
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
    @endPushOnce

    <x-status />

    <x-form class="card card-md login-split" :action="route('website.password.store')" method="POST">
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="h2 mb-2">
                    {{ __('Reset password') }}
                </h2>

                <p class="text-muted">
                    {{ __('Make sure to use a strong password to keep your account secure.') }}
                </p>
            </div>

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
        </div>
    </x-form>
</x-layouts::website.auth>
