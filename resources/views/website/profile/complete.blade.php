<x-layouts::website.auth-split :title="__('Complete your profile')">
    <x-status class="mb-3" />

    <div class="row g-0 align-items-center login-split">
        <div class="col-12 col-lg-6 login-split__form-col">
            <div class="login-split__form-wrap mx-auto">
                <x-form :action="route('website.profile.complete.store')" method="PUT">
                    <h1 class="login-split__title mb-2">{{ __('One last step to set up your account') }}</h1>
                    <p class="text-secondary mb-4">{{ __('Confirm your details and account country to continue.') }}</p>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <x-input name="first_name" :title="__('First name')" :value="$user->first_name" validation="required" autocomplete="given-name" />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="last_name" :title="__('Last name')" :value="$user->last_name" validation="required" autocomplete="family-name" />
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            @include('website.profile.partials.account-country')
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <x-radios name="gender" :title="__('Gender')" :options="\App\Enums\Gender::values()" :value="$user->gender?->value ?? \App\Enums\Gender::Male->value" validation="required" inline />
                        </div>
                    </div>

                    @include('website.profile.partials.account-consents')

                    <div class="form-footer mt-3">
                        <button type="submit" class="btn btn-brand w-100">{{ __('Continue') }}</button>
                    </div>
                </x-form>
            </div>
        </div>

        <div class="col-12 col-lg-6 login-split__illustration d-none d-lg-flex flex-column align-items-center justify-content-center">
            <img src="{{ hashed_asset('assets/images/afandy-complete-profile.webp') }}" alt="" width="1024" height="1536"
                class="login-split__illustration-img" />
        </div>
    </div>
</x-layouts::website.auth-split>
