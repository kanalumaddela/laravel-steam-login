<?php

namespace kanalumaddela\LaravelSteamLogin;

interface SteamLoginInterface
{
    public function validate();

    public function redirectToSteam();

    public function getLoginUrl();
}
