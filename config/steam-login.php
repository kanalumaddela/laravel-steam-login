<?php

return [

	/**
	 * Login route
	 */
	'login_route' => env('STEAM_LOGIN', '/login'),

	/**
	 * Return route
	 */
	'return_route' => env('STEAM_RETURN', '/auth/steam'),

	/**
	 * Timeout when validating
	 */
	'timeout' => env('STEAM_TIMEOUT', 15),

	/**
	 * Method of retrieving user's info
	 */
	'method' => env('STEAM_PROFILE_METHOD', 'xml'),

	/**
	 * API key (http://steamcommunity.com/dev/apikey)
	 */
	'api_key' => env('STEAM_API_KEY', ''),

];