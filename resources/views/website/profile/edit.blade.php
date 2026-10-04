<x-layouts::website.auth :title="__('Update Profile')">
    <x-status />

    <x-form class="card card-md" :action="route('website.profile.update')" method="PUT">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="h2 mb-2">
                    {{ __('Update Profile') }}
                </h2>

                <p class="text-muted">
                    {{ __('Here you can update your profile information.') }}
                </p>
            </div>

            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <x-input type="text" name="first_name" :title="__('First name')" value="{{ $user->first_name }}"
                        :placeholder="__('First name')" validation="required" />
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <x-input type="text" name="last_name" :title="__('Last name')" value="{{ $user->last_name }}"
                        :placeholder="__('Last name')" validation="required" />
                </div>
            </div>

            <div class="mb-3">
                <x-input type="email" name="email" :title="__('Email address')" value="{{ $user->email }}"
                    placeholder="your@email.com" validation="required|email" />
            </div>

            @include('website.profile.partials.personal-details')

            <div class="mb-3">
                <x-input type="password" name="password" :title="__('Password')" type="password"
                    placeholder="{{ __('Leave blank if you don\'t want to change it') }}"
                    validation="nullable|confirmed" />
            </div>

            <div class="mb-3">
                <x-input type="password" name="password_confirmation" :title="__('Confirm Password')" type="password"
                    placeholder="{{ __('Leave blank if you don\'t want to change it') }}" />
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">{{ __('Update Profile') }}</button>
            </div>
        </div>
    </x-form>
</x-layouts::website.auth>
