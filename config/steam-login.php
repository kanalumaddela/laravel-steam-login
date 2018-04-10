<?php

return [
    /**
     * API key (http://steamcommunity.com/dev/apikey)
     */
    'api_key' => env('STEAM_API_KEY', null),

    /*
     * Method of retrieving user's info
     */
    'method' => env('STEAM_PROFILE_METHOD', 'xml'),

    /**
     * Timeout in seconds
     */
    'timeout' => env('STEAM_TIMEOUT', 5),

    /**
     * Use the steam universe when converting to Steam:ID
     * May cause issues depending on your use case, e.g. garrymod uses STEAM_0 while newer source games use STEAM_1
     */
    'universe' => env('STEAM_UNIVERSE', false),

    /**
     * Routes used, named routes are also accepted
     */
    'routes' => [
        'callback' => env('STEAM_CALLBACK_ROUTE', '/auth/steam'),
        'login' => env('STEAM_LOGIN_ROUTE', '/login/steam')
    ],



];
