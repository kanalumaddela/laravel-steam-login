<?php

return [
    /*
     * API key (https://steamcommunity.com/dev/apikey)
     */
    'api_key' => env('STEAM_API_KEY', null),

    /*
     * Method of retrieving user's info
     */
    'method' => env('STEAM_PROFILE_METHOD', 'xml'),

    /*
     * Timeout in seconds, used for as the timeout for any requests performed
     */
    'timeout' => env('STEAM_TIMEOUT', 5),
];
