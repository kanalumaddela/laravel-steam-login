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

namespace kanalumaddela\LaravelSteamLogin\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamUser;

interface SteamLoginControllerInterface
{
    /**
     * Redirect the user to the Steam login page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToSteam(): RedirectResponse;

    /**
     * Authenticate the incoming request.
     *
     * @return mixed
     */
    public function authenticate();

    /**
     * Called when the request is successfully authenticated.
     *
     * @param \Illuminate\Http\Request                   $request
     * @param \kanalumaddela\LaravelSteamLogin\SteamUser $steamUser
     *
     * @return mixed|void
     */
    public function authenticated(Request $request, SteamUser $steamUser);
}
