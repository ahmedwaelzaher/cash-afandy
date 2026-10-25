<?php

use Redot\Models\Setting;

Setting::define('app_logo_dark')->file()->default('assets/images/logo-dark.svg')->rules(['nullable', 'image', 'max:1024']);

Setting::define('app_logo_light')->file()->default('assets/images/logo-light.svg')->rules(['nullable', 'image', 'max:1024']);

Setting::define('app_name')->array()->default([
    'en' => 'Cash Afandy',
    'ar' => 'كاش أفندى',
])->rules([
    'app_name' => ['required', 'array'],
    'app_name.*' => ['required', 'string'],
]);

Setting::define('footer_about_description')->array()->default([
    'en' => "<div>\r\n<div><span>Your smart friend in saving.</span></div>\r\n<div><span>Coupons, discounts, and offers... the right way</span></div>\r\n</div>",
    'ar' => "<p>صاحبك الشاطر في التوفير.</p>\r\n<p>كوبونات، خصومات، وعروض&hellip; بأصول.</p>",
])->rules([
    'footer_about_description' => ['nullable', 'array'],
    'footer_about_description.*' => ['nullable', 'string'],
]);

Setting::define('footer_subscribe_description')->array()->default([
    'en' => "<p>Subscribe to our newsletter and be the first to know about the latest coupons, offers, and deals.</p>",
    'ar' => "<p>اشترك في نشرتنا الإخبارية وكن أول من يعلم بأحدث الكوبونات والعروض والصفقات.</p>",
])->rules([
    'footer_subscribe_description' => ['nullable', 'array'],
    'footer_subscribe_description.*' => ['nullable', 'string'],
]);

Setting::define('website_locales')->array()->default(['ar', 'en'])->rules(['required', 'array', 'min:1']);

Setting::define('website_countries')->array()->default(['eg', 'sa'])->rules(['required', 'array', 'min:1']);

Setting::define('default_website_country')->string()->default('eg')->rules(['required', 'string']);

Setting::define('show_website_countries_dropdown')->boolean()->default(true);

Setting::define('dashboard_locales')->array()->default(['ar', 'en'])->rules(['required', 'array', 'min:1']);

Setting::define('page_loader_enabled')->boolean()->default(false);

Setting::define('service_worker_enabled')->boolean()->default(true);

Setting::define('facebook_pixel_id')->string()->default('');

Setting::define('google_analytics_property_id')->string()->default('');

Setting::define('cloudflare_turnstile_site_key')->string()->default(env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''));

Setting::define('cloudflare_turnstile_secret_key')->string()->default(env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''));

Setting::define('google_client_id')->string()->default(env('GOOGLE_CLIENT_ID', ''));

Setting::define('google_client_secret')->string()->default(env('GOOGLE_CLIENT_SECRET', ''));

Setting::define('facebook_client_id')->string()->default(env('FACEBOOK_CLIENT_ID', ''));

Setting::define('facebook_client_secret')->string()->default(env('FACEBOOK_CLIENT_SECRET', ''));

Setting::define('head_code')->string()->default('');

Setting::define('body_code')->string()->default('');

Setting::define('dashboard_sidebar_theme')->string()->default('inherit');

Setting::define('default_locale')->string()->default('ar');

Setting::define('theme')->array()->default([
    'primary' => 'red',
    'base' => 'default',
    'fonts' => [],
    'radius' => 1,
]);

Setting::define('social_facebook')->string()->default('https://www.facebook.com/cashafandy');

Setting::define('social_instagram')->string()->default('');

Setting::define('social_whatsapp')->string()->default('');

Setting::define('social_x')->string()->default('');

Setting::define('social_telegram')->string()->default('https://t.me/cashafandy');

Setting::define('social_tiktok')->string()->default('');

// Additional settings introduced by the core update.
Setting::define('app_icon_dark')->file()->default('favicon.ico')->rules(['nullable', 'image', 'max:1024']);
Setting::define('app_icon_light')->file()->default('favicon.ico')->rules(['nullable', 'image', 'max:1024']);
Setting::define('app_description')->array()->rules([
    'app_description' => ['nullable', 'array'],
    'app_description.*' => ['nullable', 'string'],
]);
Setting::define('contact_phone')->string();
Setting::define('contact_email')->string();
Setting::define('contact_address')->array();
Setting::define('social_links')->array()->default([])->rules([
    'social_links' => ['nullable', 'array'],
    'social_links.*' => ['nullable', 'array'],
    'social_links.*.icon' => ['required', 'string'],
    'social_links.*.url' => ['required', 'url'],
]);
