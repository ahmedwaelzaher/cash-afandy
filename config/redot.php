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

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Dashboard route gating is driven by Spatie permissions resolved from
    | route names. Set bypass to true while developing locally so every Gate
    | check passes without syncing or assigning permissions. Bypass is never
    | honored outside the local environment.
    |
    */

    'permissions' => [
        'bypass' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | File extensions that may be stored in the public uploads directory. The
    | extension is detected from the file contents, not the client filename.
    |
    */

    'uploads' => [
        'allowed_extensions' => [
            '7z', 'aac', 'avi', 'avif', 'bmp', 'csv', 'doc', 'docx', 'flac', 'gif',
            'gz', 'jpeg', 'jpg', 'm4a', 'mkv', 'mov', 'mp3', 'mp4', 'mpeg', 'odp',
            'ods', 'odt', 'oga', 'ogg', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'tar',
            'tif', 'tiff', 'txt', 'wav', 'webm', 'webp', 'xls', 'xlsx', 'zip',
        ],
    ],

];
