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

namespace kanalumaddela\LaravelSteamLogin;

interface SteamLoginInterface
{
    /**
     * Get the steam openid login url.
     *
     * @return string
     */
    public function loginUrl();

    /**
     * Redirect the user to steam.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect();

    /**'
     * @return \Illuminate\Http\RedirectResponse
     */
    public function return();

    /**
     * Return the steamid if validated.
     *
     * @return string|null
     */
    public function validate();
}
