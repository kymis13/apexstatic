<?php

return [
    'steam' => [
        'key' => env('STEAM_API_KEY'),
        'return_url' => env('STEAM_OPENID_RETURN_URL', env('APP_URL').'/api/steam/callback'),
        'realm' => env('APP_URL'),
        'admin_ids' => array_values(array_filter(array_map('trim', explode(',', env('ADMIN_STEAM_IDS', ''))))),
    ],

    'apex' => [
        'key' => env('APEX_API_KEY'),
        'url' => env('APEX_API_URL', 'https://api.mozambiquehe.re'),
    ],

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
];
