<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Mpdf config
    |--------------------------------------------------------------------------
    |
    | Here you can specify the configuration of the Laravel Mpdf.
    |
    */

    'mode' => '',
    'format' => 'A4',

    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'margin_header' => 0,
    'margin_footer' => 0,

    'orientation' => 'P',
    'title' => '',
    'subject' => '',
    'author' => '',

    'watermark' => '',
    'show_watermark' => false,
    'show_watermark_image' => false,
    'watermark_font' => 'sans-serif',
    'display_mode' => 'fullpage',
    'watermark_text_alpha' => 0.1,
    'watermark_image_path' => '',
    'watermark_image_alpha' => 0.2,
    'watermark_image_size' => 'D',
    'watermark_image_position' => 'P',

    'default_font' => 'ibmplexsansarabic',
    'default_font_size' => 12,
    'useOTL' => 0xFF,
    'useKashida' => 75,
    'custom_font_dir' => public_path('assets/fonts/ibm-plex-sans-arabic/'),
    'custom_font_data' => [
        'ibmplexsansarabic' => [
            'R' => 'IBMPlexSansArabic-Regular.ttf',
            'B' => 'IBMPlexSansArabic-Bold.ttf',
            'M' => 'IBMPlexSansArabic-Medium.ttf',
            'L' => 'IBMPlexSansArabic-Light.ttf',
            'S' => 'IBMPlexSansArabic-SemiBold.ttf',
            'E' => 'IBMPlexSansArabic-ExtraLight.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ],

    'auto_language_detection' => false,

    'temp_dir' => storage_path('app'),

    'pdfa' => false,
    'pdfaauto' => false,

    'use_active_forms' => false,
];
