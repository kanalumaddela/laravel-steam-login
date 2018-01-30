<?php

namespace kanalumaddela\LaravelSteamLogin;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SteamLogin implements SteamLoginInterface
{
	/**
	 * Steam OpenID URL
	 *
	 * @var string
	 */
	const OPENID_STEAM = 'https://steamcommunity.com/openid/login';

	/**
	 * OpenID Specs
	 *
	 * @var	string
	 */
	const OPENID_SPECS = 'http://specs.openid.net/auth/2.0';

	/**
	 * Steam API GetPlayerSummaries
	 *
	 * @var string
	 */
	const STEAM_API = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

	/**
	 * Steam Profile URL using 64 bit steamid
	 *
	 * @var string
	 */
	const STEAM_PROFILE = 'https://steamcommunity.com/profiles/%s';

	/**
	 * Steam Profile URL using custom URL
	 *
	 * @var string
	 */
	const STEAM_PROFILE_ID = 'https://steamcommunity.com/id/%s';

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * SteamLogin constructor.
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Generate login URL
	 *
	 * @return string
	 */
	public function loginUrl() {
		$return = url($this->request->path());

		$params = [
			'openid.ns'         => self::OPENID_SPECS,
			'openid.mode'       => 'checkid_setup',
			'openid.return_to'  => $return,
			'openid.realm'      => $this->request->getSchemeAndHttpHost(),
			'openid.identity'   => self::OPENID_SPECS.'/identifier_select',
			'openid.claimed_id' => self::OPENID_SPECS.'/identifier_select'
		];

		return self::OPENID_STEAM.'?'.http_build_query($params);
	}

	/**
	 * Redirect to Steam
	 *
	 * @return void
	 */
	public function redirect()
	{
		// TODO: Implement redirect() method.
	}

	/**
	 * Validate steam login
	 *
	 */
	public function validate()
	{
		// TODO: Implement validate() method.
	}

	/**
	 * Validate a URL
	 *
	 * @param string $url
	 * @return boolean
	 */
	private static function isUrl($url) {
		$valid = false;
		if (filter_var($url, FILTER_VALIDATE_URL)) {
			set_error_handler(function() {});
			$headers = get_headers($url);
			$httpCode = substr($headers[0], 9, 3);
			restore_error_handler();
			$valid = ($httpCode >= 200 && $httpCode <= 400);
		}

		return $valid;
	}
}