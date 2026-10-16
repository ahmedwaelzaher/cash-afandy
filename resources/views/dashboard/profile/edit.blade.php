<x-layouts::dashboard>
    <x-form class="card" :action="route('dashboard.profile.update')" method="PUT">
        <div class="card-header">
            <div class="card-title">{{ __('Edit your profile') }}</div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-auto mb-3">
                    <x-avatar :name="$admin->name" :image="$admin->profile_picture" size="xl" avatar-preview />
                </div>

                <div class="col mb-3">
                    <x-input type="file" name="profile_picture" :title="__('Profile picture')"
                        onchange="applyAvatarPreview(this, '[avatar-preview]')" />
                </div>
            </div>

            <div class="mb-3">
                <x-input name="name" :title="__('Name')" :value="$admin->name" validation="required" />
            </div>

            <div class="mb-3">
                <x-input type="email" name="email" :title="__('Email address')" :value="$admin->email"
                    validation="required|email" />
            </div>

            <div class="mb-3">
                <x-input type="password" name="password" :title="__('Password')" type="password"
                    placeholder="{{ __('Leave blank if you don\'t want to change it') }}"
                    validation="nullable|confirmed" />
            </div>

            <div class="mb-3">
                <x-input type="password" name="password_confirmation" :title="__('Confirm Password')" type="password"
                    placeholder="{{ __('Leave blank if you don\'t want to change it') }}" />
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="reset" class="btn">{{ __('Reset') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
        </div>
    </x-form>
</x-layouts::dashboard>
