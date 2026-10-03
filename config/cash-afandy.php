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
    | Settings
    |--------------------------------------------------------------------------
    |
    | Persisted application settings schema. Each setting may define a default
    | value and request validation rules for the dashboard settings form.
    |
    */

    'settings' => [
        'app_logo_dark' => [
            'default' => 'assets/images/logo-dark.svg',
            'rules' => ['nullable', 'image', 'max:1024'],
        ],
        'app_logo_light' => [
            'default' => 'assets/images/logo-light.svg',
            'rules' => ['nullable', 'image', 'max:1024'],
        ],
        'app_name' => [
            'default' => [
                'en' => 'Cash Afandy',
                'ar' => 'كاش أفندى',
            ],
            'rules' => [
                'app_name' => ['required', 'array'],
                'app_name.*' => ['required', 'string'],
            ],
        ],
        'footer_about_description' => [
            'default' => [
                'en' => "Who doesn't like getting the best deal when buying a product? Cash Afandy is here to help you get the highest cashback with coupons on the best stores, in addition to a service that continuously tracks offers from trusted merchants only. You can also follow Cash Afandy on social media and be part of the Cash Afandy family.",
                'ar' => 'من لا يحب الحصول على أفضل صفقة عند شراء منتج؟ كاش أفندى هنا لمساعدتك في الحصول على أعلى كاش باك مع كوبونات من أفضل المتاجر، بالإضافة إلى خدمة تتابع العروض باستمرار من تجار موثوقين فقط. يمكنك أيضًا متابعة كاش أفندى على مواقع التواصل الاجتماعي والانضمام إلى عائلة كاش أفندى.',
            ],
            'rules' => [
                'footer_about_description' => ['nullable', 'array'],
                'footer_about_description.*' => ['nullable', 'string'],
            ],
        ],
        'footer_subscribe_description' => [
            'default' => [
                'en' => 'Subscribe to our newsletter and be the first to know about the latest coupons, cashback offers, and deals.',
                'ar' => 'اشترك في نشرتنا الإخبارية وكن أول من يعلم بأحدث الكوبونات وعروض الكاش باك والصفقات.',
            ],
            'rules' => [
                'footer_subscribe_description' => ['nullable', 'array'],
                'footer_subscribe_description.*' => ['nullable', 'string'],
            ],
        ],
        'website_locales' => [
            'default' => ['en', 'ar'],
            'rules' => ['required', 'array', 'min:1'],
        ],
        'website_countries' => [
            'default' => ['eg', 'sa'],
            'rules' => ['required', 'array', 'min:1'],
        ],
        'default_website_country' => [
            'default' => 'eg',
            'rules' => ['required', 'string'],
        ],
        'show_website_countries_dropdown' => [
            'default' => true,
        ],
        'dashboard_locales' => [
            'default' => ['en', 'ar'],
            'rules' => ['required', 'array', 'min:1'],
        ],
        'page_loader_enabled' => [
            'default' => false,
        ],
        'service_worker_enabled' => [
            'default' => true,
        ],
        'facebook_pixel_id' => [
            'default' => '',
        ],
        'google_analytics_property_id' => [
            'default' => '',
        ],
        'cloudflare_turnstile_site_key' => [
            'default' => '',
        ],
        'cloudflare_turnstile_secret_key' => [
            'default' => '',
        ],
        'google_client_id' => [
            'default' => '',
        ],
        'google_client_secret' => [
            'default' => '',
        ],
        'facebook_client_id' => [
            'default' => '',
        ],
        'facebook_client_secret' => [
            'default' => '',
        ],
        'head_code' => [
            'default' => '',
        ],
        'body_code' => [
            'default' => '',
        ],
        'dashboard_sidebar_theme' => [
            'default' => 'inherit',
        ],
        'theme' => [
            'default' => [
                'primary' => 'red',
                'base' => 'default',
                'font' => 'serif',
                'radius' => 1,
            ],
        ],
        'social_facebook' => [
            'default' => '',
        ],
        'social_instagram' => [
            'default' => '',
        ],
        'social_whatsapp' => [
            'default' => '',
        ],
        'social_x' => [
            'default' => '',
        ],
        'social_telegram' => [
            'default' => '',
        ],
        'social_tiktok' => [
            'default' => '',
        ],
    ],
];
