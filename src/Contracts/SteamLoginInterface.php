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

    /**
     * Is the current request valid for
     *
     * @param \Illuminate\Http\Request|null $request
     *
     * @return bool
     */
    public function validRequest(?Request $request = null): bool;
}
