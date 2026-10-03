<x-layouts::website.auth :title="__('Complete your profile')">
    <x-status />

    <x-form class="card card-md" :action="route('website.profile.complete.store')" method="PUT">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="h2 mb-2">{{ __('Complete your profile') }}</h2>

                <p class="text-muted">
                    {{ __('Please confirm your details and fill in anything missing to continue.') }}
                </p>
            </div>

            @include('website.profile.partials.personal-details', ['splitBirthdate' => true])

            <div class="form-footer">
                <button type="submit" class="btn btn-brand w-100">{{ __('Continue') }}</button>
            </div>
        </div>
    </x-form>
</x-layouts::website.auth>
