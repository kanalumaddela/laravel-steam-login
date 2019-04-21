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

namespace kanalumaddela\LaravelSteamLogin\Interfaces;

use Illuminate\Http\RedirectResponse;

interface SteamLoginInterface
{
    /**
     * Return the steamid if validated.
     *
     * @return string|null
     */
    public function validate(): ?string;

    /**
     * Redirect the user to steam's login page.
     *
     * @return RedirectResponse
     */
    public function redirectToSteam(): RedirectResponse;

    /**
     * Return the steam login url.
     *
     * @return string
     */
    public function getLoginUrl(): string;
}
