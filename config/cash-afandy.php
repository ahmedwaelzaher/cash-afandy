<?php

return [
    'notifications' => [
        'push_enabled' => (bool) env('FIREBASE_PUSH_ENABLED', false),
        'sdk_version' => '13.1.0',
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
        'firebase' => [
            'apiKey' => env('FIREBASE_API_KEY'),
            'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
            'projectId' => env('FIREBASE_PROJECT_ID'),
            'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
            'appId' => env('FIREBASE_APP_ID'),
        ],
    ],
];
