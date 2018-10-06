<?php

namespace kanalumaddela\LaravelSteamLogin;

use Illuminate\Http\RedirectResponse;

interface SteamLoginInterface
{
    public function validate();

    public function redirectToSteam(): RedirectResponse;

    public function getLoginUrl(): string;
}
