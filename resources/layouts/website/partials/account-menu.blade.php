@auth('users')
    <div class="dropdown">
        <button type="button" @class(['btn site-account-button', 'btn-icon btn-ghost-secondary' => $compact, 'btn-primary' => ! $compact])
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Profile') }}">
            <x-icon icon="ti ti-user" />
            <span @class(['visually-hidden' => $compact])>{{ __('Profile') }}</span>
        </button>

        <div class="dropdown-menu dropdown-menu-end">
            <span class="dropdown-header text-break">{{ current_user()->full_name }}</span>

            <a class="dropdown-item" href="{{ route('website.profile.edit') }}">{{ __('Profile') }}</a>

            <x-form :action="route('website.logout')" method="POST" disable-validation>
                <button type="submit" class="dropdown-item">{{ __('Logout') }}</button>
            </x-form>
        </div>
    </div>
@else
    <a href="{{ route('website.login') }}" @class(['btn site-account-button', 'btn-icon btn-ghost-secondary' => $compact, 'btn-primary' => ! $compact]) aria-label="{{ __('Login') }}">
        <x-icon icon="ti ti-user" />
        <span @class(['visually-hidden' => $compact])>{{ __('Login') }}</span>
    </a>
@endauth
