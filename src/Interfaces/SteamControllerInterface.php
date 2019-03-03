<?php

namespace kanalumaddela\LaravelSteamLogin\Interfaces;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamUser;

interface SteamControllerInterface
{
    public function redirectToSteam(): RedirectResponse;

    public function authenticate();

    public function authenticated(Request $request,  SteamUser $steamUser);
}