<?php

namespace kanalumaddela\LaravelSteamLogin;

interface SteamLoginInterface
{
	public function loginUrl();
	public function return($path);
	public function validate();
}