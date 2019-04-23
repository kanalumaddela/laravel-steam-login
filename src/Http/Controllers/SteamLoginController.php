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

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamUser;
use const JSON_PRETTY_PRINT;

class SteamLoginController extends AbstractSteamLoginController
{
    /**
     * Called when the request is successfully authenticated.
     *
     * @param \Illuminate\Http\Request                   $request
     * @param \kanalumaddela\LaravelSteamLogin\SteamUser $steamUser
     *
     * @return mixed|void
     */
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        return JsonResponse::create($steamUser->getUserInfo())->setEncodingOptions(JSON_PRETTY_PRINT);
    }
}
