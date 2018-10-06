<?php

return [
    /*
     * API key (https://steamcommunity.com/dev/apikey)
     */
    'api_key' => env('STEAM_LOGIN_API_KEY', null),

    /*
     * Method of retrieving user's info
     */
    'method' => env('STEAM_LOGIN_PROFILE_METHOD', 'xml'),

    /*
     * Timeout in seconds, used for as the timeout for any requests performed
     */
    'timeout' => env('STEAM_LOGIN_TIMEOUT', 5),

    /*
     * Route override options
     */
    'routes' => [
        'enabled' => env('STEAM_LOGIN_ROUTE_ENABLED', true),
        'login' => [
            'path' => env('STEAM_LOGIN_ROUTE_PATH', 'login/steam'),
            'name' => env('STEAM_LOGIN_ROUTE_NAME', 'login.steam'),
        ],
        'auth' => [
            'path' => env('STEAM_LOGIN_AUTH_PATH', 'auth/steam'),
            'name' => env('STEAM_LOGIN_AUTH_NAME', 'auth.steam'),
        ]
    ],
];
