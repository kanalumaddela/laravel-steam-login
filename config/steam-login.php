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
     * Route names
     */
    'routes' => [
        'login'   => env('STEAM_LOGIN_ROUTE_NAME', 'login.steam'),
        'auth'    => env('STEAM_AUTH_ROUTE_NAME', 'auth.steam'),
    ],
];
