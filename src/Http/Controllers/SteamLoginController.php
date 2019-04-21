<?php
/**
 * Laravel Steam Login
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamUser;

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

    }
}