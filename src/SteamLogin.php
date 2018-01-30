<?php

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
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
	 * Player's steamid (64 bit)
	 *
	 * @var int
	 */
	public $steamid;

	/**
	 * Player's SteamID
	 *
	 * @var string
	 */
	public $steamid2;

	/**
	 * Player's SteamID3
	 *
	 * @var string
	 */
	public $steamid3;

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
	 * Check if valid post steam login
	 *
	 * @return boolean
	 */
	public function validRequest()
	{
		return $this->request->has('openid_assoc_handle') && $this->request->has('openid_claimed_id') && $this->request->has('openid_sig') && $this->request->has('openid_signed');
	}

	/**
	 * Generate login URL
	 *
	 * @return string
	 */
	public function loginUrl() {
		$return = url(Config::get('steam-login.return_route').'?return='.$this->request->path());

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
	 * Redirect back to their original page
	 *
	 */
	public function return($path)
	{
		return redirect(url($path));
	}

	/**
	 * Validate steam login
	 *
	 * @return boolean
	 */
	public function validate()
	{
		if (!$this->validRequest()) {
			return false;
		}

		try {
			$params = [
				'openid.assoc_handle' => $_GET['openid_assoc_handle'],
				'openid.signed'       => $_GET['openid_signed'],
				'openid.sig'          => $_GET['openid_sig'],
				'openid.ns'           => self::OPENID_SPECS,
			];

			$signed = explode(',', $_GET['openid_signed']);

			foreach ($signed as $item) {
				$value = $_GET['openid_' . str_replace('.', '_', $item)];
				$params['openid.' . $item] = get_magic_quotes_gpc() ? stripslashes($value) : $value;
			}

			$params['openid.mode'] = 'check_authentication';

			$data =  http_build_query($params);

			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' =>
						"Accept-language: en\r\n".
						"Content-type: application/x-www-form-urlencoded\r\n" .
						"Content-Length: " . strlen($data) . "\r\n",
					'content' => $data,
					'timeout' => Config::get('steam-login.timeout'),
				),
			));

			$result = file_get_contents(self::OPENID_STEAM, false, $context);

			preg_match("#^http://steamcommunity.com/openid/id/([0-9]{17,25})#", $_GET['openid_claimed_id'], $matches);
			$steamid = is_numeric($matches[1]) ? $matches[1] : 0;
			$steamid = preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamid : null;
			$this->steamid = $steamid;
		} catch (Exception $e) {
			$steamid = null;
		}

		if (is_null($steamid)) {
			throw new RuntimeException('Steam Auth failed/timed out');
		}

		$this->convert($steamid);
		return true;
	}

	/**
	 * Convert a player's 64 bit steamid
	 *
	 * @param $steamid
	 */
	public function convert($steamid) {
		// convert to SteamID
		$authserver = bcsub($steamid, '76561197960265728') & 1;
		$authid = (bcsub($steamid, '76561197960265728') - $authserver) / 2;
		$this->steamid2 = "STEAM_0:$authserver:$authid";

		// convert to SteamID3
		$steamid2_split = explode(':', $this->steamid2);
		$y = (int)$steamid2_split[1];
		$z = (int)$steamid2_split[2];
		$this->steamid3 = '[U:1:'.($z*2+$y).']';
	}


	/**
	 * Get player's information
	 *
	 * @param string $method
	 * @return \stdClass
	 */
	public function userInfo() {
		$info = new \stdClass();
		switch (Config::get('steam-login.method')) {
			case 'xml':
				$data = simplexml_load_string(file_get_contents(sprintf(self::STEAM_PROFILE.'/?xml=1', $this->steamid)),'SimpleXMLElement',LIBXML_NOCDATA);
				$info->customURL = (string)$data->customURL;
				$info->joined = (string)$data->memberSince;

				$info->name = (string)$data->steamID;
				$info->realName = (string)$data->realname;
				$info->playerState = ucfirst((string)$data->onlineState);
				$info->stateMessage = (string)$data->stateMessage;
				$info->privacyState = ucfirst((string)$data->privacyState);
				$info->visibilityState = (int)$data->visibilityState;
				$info->avatarSmall = (string)$data->avatarIcon;
				$info->avatarMedium = (string)$data->avatarMedium;
				$info->avatarLarge =(string) $data->avatarFull;
				$info->profileURL = !empty((string)$data->customURL) ? sprintf(self::STEAM_PROFILE_ID, (string)$data->customURL) : sprintf(self::STEAM_PROFILE, $this->steamid);
				$info->joined = !empty($data->joined) ? $data->joined : null;
				break;
			case 'api':
				$data = json_decode(file_get_contents(sprintf(self::STEAM_API, Config::get('steam-login.api_key'), $this->steamid)));
				$data = $data->response->players[0];
				switch ($data->personastate) {
					case 0:
						$data->personastate = 'Offline';
						break;
					case 1:
						$data->personastate = 'Online';
						break;
					case 2:
						$data->personastate = 'Busy';
						break;
					case 3:
						$data->personastate = 'Away';
						break;
					case 4:
						$data->personastate = 'Snooze';
						break;
					case 5:
						$data->personastate = 'Looking to trade';
						break;
					case 6:
						$data->personastate = 'Looking to play';
						break;
				}
				$info->name = $data->personaname;
				$info->realName = $data->realname ?? null;
				$info->playerState = $data->personastate != 0 ? 'Online' : 'Offline';
				$info->stateMessage = $data->personastate;
				$info->privacyState = ($data->communityvisibilitystate == 1 || $data->communityvisibilitystate == 2) ? 'Private' : 'Public';
				$info->visibilityState = $data->communityvisibilitystate;
				$info->avatarSmall = $data->avatar;
				$info->avatarMedium = $data->avatarmedium;
				$info->avatarLarge = $data->avatarfull;
				$info->profileURL = str_replace('http://', 'https://', $data->profileurl);
				$info->joined = isset($data->timecreated) ? date('F jS, Y', $data->timecreated) : null;
				break;
			default:
				break;
		}

		return $info;
	}

	/**
	 * Return the URL of Steam Login buttons
	 *
	 * @param string $type
	 * @return string
	 */
	public static function loginButton($type = 'small') {
		return 'https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_0'.($type == 'small' ? 1 : 2).'.png';
	}

}