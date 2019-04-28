<?php
/**
 * Laravel Steam Login.
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

return [
    /*
     * Login route
     */
    'login_route'  => env('STEAM_LOGIN', '/login'),

    /*
     * Return route
     */
    'return_route' => env('STEAM_RETURN', '/auth/steam'),

    /*
     * Timeout when validating
     */
    'timeout'      => env('STEAM_TIMEOUT', 5),

    /*
     * Method of retrieving user's info
     */
    'method'       => env('STEAM_PROFILE_METHOD', 'xml'),

    /*
     * API key (http://steamcommunity.com/dev/apikey)
     */
    'api_key'      => env('STEAM_API_KEY', ''),
];
