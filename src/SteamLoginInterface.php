<?php

namespace kanalumaddela\LaravelSteamLogin;

interface SteamLoginInterface
{
	public function loginUrl();
	public function redirect();
	public function return();
	public function validate();
}