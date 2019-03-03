<?php

namespace kanalumaddela\LaravelSteamLogin\Interfaces;

use Illuminate\Http\RedirectResponse;

interface SteamLoginInterface
{
    /**
     * Return the steamid being validated or not.
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
