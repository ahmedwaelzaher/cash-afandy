<x-layouts::website.auth :title="__('Complete your profile')">
    <x-status class="mb-3" />
    <x-form class="card card-md" :action="route('website.profile.complete.store')" method="PUT">
        <div class="card-body">
            <h1 class="h2 mb-2">{{ __('One last step to set up your account') }}</h1>
            <p class="text-secondary mb-4">{{ __('Confirm your details and account country to continue.') }}</p>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <x-input name="first_name" :title="__('First name')" :value="$user->first_name" validation="required" autocomplete="given-name" />
                </div>
                <div class="col-12 col-md-6">
                    <x-input name="last_name" :title="__('Last name')" :value="$user->last_name" validation="required" autocomplete="family-name" />
                </div>
            </div>
            <div class="mb-3">
                <x-radios name="gender" :title="__('Gender')" :options="\App\Enums\Gender::values()" :value="$user->gender?->value" validation="required" inline />
            </div>
            @include('website.profile.partials.account-country')
            <button type="submit" class="btn btn-brand w-100">{{ __('Confirm country and continue') }}</button>
        </div>
    </x-form>
</x-layouts::website.auth>
