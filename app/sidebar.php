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
        ->icon('ti ti-home')
        ->route('dashboard.index'),

    Item::make()
        ->title(__('Website'))
        ->icon('ti ti-world')
        ->children([
            Item::make()
                ->title(__('Users'))
                ->route('dashboard.users.index')
                ->icon('ti ti-users'),

            Item::make()
                ->title(__('Static Pages'))
                ->route('dashboard.static-pages.index')
                ->icon('ti ti-file-text')
                ->hidden(config('redot.features.website.enabled') === false),

            Item::make()
                ->title(__('Subscribers'))
                ->route('dashboard.subscribers.index')
                ->icon('ti ti-mail'),

            Item::make()
                ->title(__('Shortened URLs'))
                ->route('dashboard.shortened-urls.index')
                ->icon('ti ti-link')
                ->hidden(config('redot.features.website.enabled') === false),
        ]),

    Item::make()
        ->title(__('Access'))
        ->icon('ti ti-shield-lock')
        ->children([
            Item::make()
                ->title(__('Admins'))
                ->route('dashboard.admins.index')
                ->icon('ti ti-user-shield'),

            Item::make()
                ->title(__('Roles'))
                ->route('dashboard.roles.index')
                ->icon('ti ti-lock'),

            Item::make()
                ->title(__('Impersonate Admin'))
                ->route('dashboard.impersonate-admins.create')
                ->icon('ti ti-spy'),

            Item::make()
                ->title(__('Impersonate User'))
                ->route('dashboard.impersonate-users.create')
                ->icon('ti ti-mask'),
        ]),

    Item::make()
        ->title(__('Cash Afandy'))
        ->icon('ti ti-coin')
        ->children([
            Item::make()
                ->title(__('Categories'))
                ->route('dashboard.categories.index')
                ->icon('ti ti-tags'),

            Item::make()
                ->title(__('Clients'))
                ->route('dashboard.clients.index')
                ->icon('ti ti-building-store'),

            Item::make()
                ->title(__('Brokers'))
                ->route('dashboard.brokers.index')
                ->icon('ti ti-heart-handshake'),

            Item::make()
                ->title(__('Coupons'))
                ->route('dashboard.coupons.index')
                ->icon('ti ti-ticket'),

            Item::make()
                ->title(__('Cashbacks'))
                ->route('dashboard.cashbacks.index')
                ->icon('ti ti-coin'),

            Item::make()
                ->title(__('News'))
                ->route('dashboard.news.index')
                ->icon('ti ti-news'),

            Item::make()
                ->title(__('Posts'))
                ->route('dashboard.posts.index')
                ->icon('ti ti-article'),

            Item::make()
                ->title(__('Sliders'))
                ->route('dashboard.sliders.index')
                ->icon('ti ti-photo'),
        ]),

    Item::make()
        ->title(__('System'))
        ->icon('ti ti-settings')
        ->children([
            Item::make()
                ->title(__('Settings'))
                ->route('dashboard.settings.edit')
                ->icon('ti ti-adjustments'),

            Item::make()
                ->title(__('Languages'))
                ->route('dashboard.languages.index')
                ->icon('ti ti-language'),

            Item::make()
                ->title(__('Backups'))
                ->route('dashboard.backups.index')
                ->icon('ti ti-database-export'),
            Item::make()
                ->title(__('Server Stats'))
                ->route('dashboard.server-stats.index')
                ->icon('ti ti-server'),
            Item::make()
                ->title(__('Failed Jobs'))
                ->route('dashboard.failed-jobs.index')
                ->icon('ti ti-alert-circle'),
        ]),

    Item::make()
        ->title(__('Tools'))
        ->icon('ti ti-tools')
        ->children([
            Item::make()
                ->title(__('Memos'))
                ->route('dashboard.memos.index')
                ->icon('ti ti-note'),

            Item::make()
                ->title(__('QR Code'))
                ->route('dashboard.qr-code.index')
                ->icon('ti ti-qrcode'),

            Item::make()
                ->title(__('Notifications'))
                ->route('dashboard.notifications.index')
                ->icon('ti ti-bell'),

            Item::make()
                ->title(__('Send Notification'))
                ->route('dashboard.admin-notifications.create')
                ->icon('ti ti-bell-plus'),
        ]),
]);
