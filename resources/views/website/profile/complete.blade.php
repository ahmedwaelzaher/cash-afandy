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

            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <x-input type="text" name="first_name" :title="__('First name')" :value="old('first_name', auth('users')->user()->first_name)"
                        :placeholder="__('First name')" validation="required" />
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <x-input type="text" name="last_name" :title="__('Last name')" :value="old('last_name', auth('users')->user()->last_name)"
                        :placeholder="__('Last name')" validation="required" />
                </div>
            </div>

            @include('website.profile.partials.personal-details', ['splitBirthdate' => true])

            <div class="form-footer">
                <button type="submit" class="btn btn-brand w-100">{{ __('Continue') }}</button>
            </div>
        </div>
    </x-form>
</x-layouts::website.auth>
