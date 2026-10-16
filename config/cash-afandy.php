<?php

return [

    /*--------------------------------------------------------------------------
    | Redot Features
    |--------------------------------------------------------------------------
    |
    | The features that are enabled for the application, You can enable or
    | disable features as per your requirements.
    |
    */

    'features' => [
        'website-api' => [
            'enabled' => false,
        ],

        'dashboard-api' => [
            'enabled' => false,
            'prefix' => 'dashboard',
        ],

        'website' => [
            'enabled' => true,
        ],

        'dashboard' => [
            'enabled' => true,
            'prefix' => 'dashboard',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | The list of available locales for the website and dashboard.
    |
    */

    'locales' => [
        [
            'code' => 'en',
            'name' => 'English',
            'is_rtl' => false,
        ],

        [
            'code' => 'ar',
            'name' => 'العربية',
            'is_rtl' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Route-level behavior that affects URL generation and fallback redirects.
    |
    */

    'routing' => [
        'append_locale_to_url' => true,
        'redirect_non_locale_urls' => true,
    ],

];
