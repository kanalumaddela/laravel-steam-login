<?php

namespace kanalumaddela\LaravelSteamLogin;

interface SteamLoginInterface
{
	public function loginUrl($return);
	public function redirect();
	public function validate();
}