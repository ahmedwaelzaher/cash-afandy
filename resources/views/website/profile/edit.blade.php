@php
    $activeSection = $errors->hasAny(['notifications_enabled', 'notification_types', 'notification_types.*', 'notification_type_options', 'notification_type_options.*']) || request('section') === 'notifications'
        ? 'notifications'
        : ($errors->hasAny(['password', 'password_confirmation']) ? 'password' : 'profile');
    $sections = [
        'profile' => ['title' => __('Profile'), 'icon' => 'ti ti-user'],
        'password' => ['title' => __('Password'), 'icon' => 'ti ti-lock'],
        'notifications' => ['title' => __('Notifications'), 'icon' => 'ti ti-bell'],
    ];
@endphp

<x-layouts::website :title="__('Account')">
    <section class="site-static-page py-4" aria-labelledby="account-title">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-6 fw-bold mb-2" id="account-title">{{ __('Account') }}</h1>
                <p class="text-secondary mb-0">{{ __('Here you can update your profile information.') }}</p>
            </header>

            <x-status class="mb-3" />

            <div class="row g-4 align-items-start">
                <aside class="col-12 col-lg-3">
                    <div class="card p-3">
                        <h2 class="h3 mb-3 d-none d-lg-block">{{ __('Account') }}</h2>
                        <button type="button" class="btn btn-ghost-secondary w-100 justify-content-between d-lg-none" data-bs-toggle="collapse" data-bs-target="#account-sections" aria-expanded="false" aria-controls="account-sections">
                            {{ __('Account') }}
                            <x-icon icon="ti ti-chevron-down" class="m-0" />
                        </button>
                        <nav class="nav flex-column gap-1 collapse d-lg-flex" id="account-sections" role="tablist" aria-orientation="vertical" aria-label="{{ __('Account') }}">
                            @foreach ($sections as $key => $section)
                                <a href="#account-{{ $key }}" id="account-{{ $key }}-tab" @class(['site-store-filter nav-link d-flex align-items-center gap-3 px-3 py-3', 'active' => $activeSection === $key])
                                    data-bs-toggle="tab" role="tab" aria-controls="account-{{ $key }}" aria-selected="{{ $activeSection === $key ? 'true' : 'false' }}" tabindex="{{ $activeSection === $key ? '0' : '-1' }}">
                                    <x-icon :icon="$section['icon']" class="flex-shrink-0" />
                                    {{ $section['title'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <div class="col-12 col-lg-9 tab-content">
                    <div @class(['tab-pane', 'active' => $activeSection === 'profile']) id="account-profile" role="tabpanel" aria-labelledby="account-profile-tab" tabindex="0">
                        <h2 class="h2 lh-1 mb-3">{{ $sections['profile']['title'] }}</h2>
                        <x-form class="card card-md" :action="route('website.profile.update')" method="PUT">
                            <div class="card-body p-lg-4">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <x-input type="text" name="first_name" :title="__('First name')" :value="$user->first_name" :placeholder="__('First name')" autocomplete="given-name" validation="required" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-input type="text" name="last_name" :title="__('Last name')" :value="$user->last_name" :placeholder="__('Last name')" autocomplete="family-name" validation="required" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-input type="email" name="email" :title="__('Email address')" :value="$user->email" placeholder="your@email.com" autocomplete="email" validation="required|email" />
                                    </div>
                                    @include('website.profile.partials.personal-details', ['fieldClass' => 'col-12 col-md-6'])
                                </div>
                                <div class="mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary px-4">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </x-form>
                    </div>

                    <div @class(['tab-pane', 'active' => $activeSection === 'password']) id="account-password" role="tabpanel" aria-labelledby="account-password-tab" tabindex="0">
                        <h2 class="h2 lh-1 mb-3">{{ $sections['password']['title'] }}</h2>
                        <x-form class="card card-md" :action="route('website.profile.update')" method="PUT">
                            <div class="card-body p-lg-4">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <x-input type="password" name="password" :title="__('Password')" :placeholder="__('Leave blank if you don\'t want to change it')" autocomplete="new-password" validation="nullable|confirmed" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <x-input type="password" name="password_confirmation" :title="__('Confirm Password')" :placeholder="__('Leave blank if you don\'t want to change it')" autocomplete="new-password" />
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary px-4">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </x-form>
                    </div>

                    <div @class(['tab-pane', 'active' => $activeSection === 'notifications']) id="account-notifications" role="tabpanel" aria-labelledby="account-notifications-tab" tabindex="0">
                        <h2 class="h2 lh-1 mb-3">{{ $sections['notifications']['title'] }}</h2>
                        @include('website.profile.partials.browser-notifications')
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
