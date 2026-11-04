@auth('users')
    <div class="dropdown">
        <button type="button" @class(['btn site-account-button', 'btn-icon btn-ghost-secondary' => $compact, 'btn-primary' => ! $compact])
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Profile') }}">
            <x-icon icon="ti ti-user" class="flex-shrink-0" />
            <span @class(['site-account-name text-truncate', 'visually-hidden' => $compact]) title="{{ current_user()->full_name }}">{{ current_user()->full_name }}</span>
        </button>

        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="{{ route('website.profile.edit') }}">
                <span class="dropdown-item-icon"><x-icon icon="ti ti-user" /></span>
                <span class="dropdown-item-title">{{ __('Profile') }}</span>
            </a>

            <a class="dropdown-item" href="{{ route('website.favorites.index') }}">
                <span class="dropdown-item-icon"><x-icon icon="ti ti-heart" /></span>
                <span class="dropdown-item-title">{{ __('Favorite offers') }}</span>
            </a>

            <x-form :action="route('website.logout')" method="POST" disable-validation>
                <button type="submit" class="dropdown-item">
                    <span class="dropdown-item-icon"><x-icon icon="ti ti-logout" /></span>
                    <span class="dropdown-item-title">{{ __('Logout') }}</span>
                </button>
            </x-form>
        </div>
    </div>
@else
    <a href="{{ route('website.login') }}" @class(['btn site-account-button', 'btn-icon btn-ghost-secondary' => $compact, 'btn-primary' => ! $compact]) aria-label="{{ __('Login') }}">
        <x-icon icon="ti ti-user" />
        <span @class(['visually-hidden' => $compact])>{{ __('Login') }}</span>
    </a>
@endauth
