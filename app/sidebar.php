<?php

use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

return Sidebar::make([

    /*
    |--------------------------------------------------------------------------
    | Dashboard Sidebar Menu
    |--------------------------------------------------------------------------
    |
    | Here you can define the sidebar menu for your application. It will be
    | displayed on the dashboard page.
    |
    | Each item can have either a "route" or "url". If you set the "route"
    | key, the URL will be generated using the Laravel's route() helper. If
    | you set the "url" key, the URL will be used as is.
    |
    | Route parameters can be passed as an array to the "parameters" key.
    |
    | You can use 'hidden' key to hide the item if the condition is true.
    | The condition can be a boolean value or a callable function that returns
    | a boolean value. If the condition is true, the item will be hidden.
    |
    | If the item has children, it will be displayed as a dropdown menu. Each
    | child item have the same structure as the parent item, except it can't
    | have children.
    |
    */

    Item::make()
        ->title(__('Dashboard'))
        ->icon('fa fa-home')
        ->route('dashboard.index'),

    Item::make()
        ->title(__('Website Management'))
        ->icon('fa fa-globe')
        ->children([
            Item::make()
                ->title(__('Users Management'))
                ->route('dashboard.users.index')
                ->icon('fa fa-users'),

            Item::make()
                ->title(__('Static Pages'))
                ->route('dashboard.static-pages.index')
                ->icon('fa fa-file-text')
                ->hidden(config('redot.features.website.enabled') === false),

            Item::make()
                ->title(__('Subscribers'))
                ->route('dashboard.subscribers.index')
                ->icon('fa fa-envelope'),
        ]),

    Item::make()
        ->title(__('Utilities'))
        ->icon('fa fa-clipboard')
        ->children([
            Item::make()
                ->title(__('Memos'))
                ->route('dashboard.memos.index')
                ->icon('fa fa-sticky-note'),

            Item::make()
                ->title(__('QR Code'))
                ->route('dashboard.qr-code.index')
                ->icon('fa fa-qrcode'),

            Item::make()
                ->title(__('Shortened URLs'))
                ->route('dashboard.shortened-urls.index')
                ->icon('fa fa-link')
                ->hidden(config('redot.features.website.enabled') === false),

            Item::make()
                ->title(__('Send Notification'))
                ->route('dashboard.admin-notifications.create')
                ->icon('fa fa-bell'),

            Item::make()
                ->title(__('Impersonate Admin'))
                ->route('dashboard.impersonate-admins.create')
                ->icon('fa fa-user-secret'),

            Item::make()
                ->title(__('Impersonate User'))
                ->route('dashboard.impersonate-users.create')
                ->icon('fa fa-user-ninja'),
        ]),

    Item::make()
        ->title(__('Cash Afandy'))
        ->icon('fa fa-piggy-bank')
        ->children([
            Item::make()
                ->title(__('Categories'))
                ->route('dashboard.categories.index')
                ->icon('fa fa-tags'),

            Item::make()
                ->title(__('Clients'))
                ->route('dashboard.clients.index')
                ->icon('fa fa-store'),

            Item::make()
                ->title(__('Brokers'))
                ->route('dashboard.brokers.index')
                ->icon('fa fa-handshake'),

            Item::make()
                ->title(__('Coupons'))
                ->route('dashboard.coupons.index')
                ->icon('fa fa-ticket'),

            Item::make()
                ->title(__('Cashbacks'))
                ->route('dashboard.cashbacks.index')
                ->icon('fa fa-piggy-bank'),

            Item::make()
                ->title(__('News'))
                ->route('dashboard.news.index')
                ->icon('fa fa-newspaper'),

            Item::make()
                ->title(__('Posts'))
                ->route('dashboard.posts.index')
                ->icon('fa fa-blog'),

            Item::make()
                ->title(__('Sliders'))
                ->route('dashboard.sliders.index')
                ->icon('fa fa-images'),
        ]),

    Item::make()
        ->title(__('Settings'))
        ->icon('fa fa-cog')
        ->children([
            Item::make()
                ->title(__('Profile'))
                ->route('dashboard.profile.edit')
                ->icon('fa fa-user'),

            Item::make()
                ->title(__('Roles'))
                ->route('dashboard.roles.index')
                ->icon('fa fa-lock'),

            Item::make()
                ->title(__('Admins'))
                ->route('dashboard.admins.index')
                ->icon('fa fa-user-shield'),

            Item::make()
                ->title(__('Settings'))
                ->route('dashboard.settings.edit')
                ->icon('fa fa-cogs'),

            Item::make()
                ->title(__('Languages'))
                ->route('dashboard.languages.index')
                ->icon('fa fa-language'),
        ]),
]);
