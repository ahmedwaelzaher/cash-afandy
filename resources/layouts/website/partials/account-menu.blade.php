@auth('users')
    <div class="dropdown">
        <button type="button" @class(['btn site-account-button', 'btn-icon btn-ghost-secondary' => $compact, 'btn-primary' => ! $compact])
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Profile') }}">
            <x-icon icon="ti ti-user" class="flex-shrink-0" />
            <span @class(['site-account-name text-truncate', 'visually-hidden' => $compact]) title="{{ current_user()->full_name }}">{{ current_user()->full_name }}</span>
        </button>

        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="{{ route('website.profile.edit') }}">
                <span class="dropdown-item-icon d-inline-flex align-items-center justify-content-center"><x-icon class="m-0 lh-1" icon="ti ti-user" /></span>
                <span class="dropdown-item-title lh-1">{{ __('Profile') }}</span>
            </a>

            <a class="dropdown-item" href="{{ route('website.favorites.index') }}">
                <span class="dropdown-item-icon d-inline-flex align-items-center justify-content-center"><x-icon class="m-0 lh-1" icon="ti ti-heart" /></span>
                <span class="dropdown-item-title lh-1">{{ __('Favorite offers') }}</span>
            </a>

            <a class="dropdown-item" href="{{ route('website.wallet.show') }}">
                <span class="dropdown-item-icon d-inline-flex align-items-center justify-content-center"><x-icon class="m-0 lh-1" icon="ti ti-coin" /></span>
                <span class="dropdown-item-title lh-1">{{ __('Balance') }}</span>
            </a>

            <x-form :action="route('website.logout')" method="POST" disable-validation>
                <button type="submit" class="dropdown-item">
                    <span class="dropdown-item-icon d-inline-flex align-items-center justify-content-center"><x-icon class="m-0 lh-1" icon="ti ti-logout" /></span>
                    <span class="dropdown-item-title lh-1">{{ __('Logout') }}</span>
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
