<?php

namespace kanalumaddela\LaravelSteamLogin;

use Illuminate\Support\Facades\Config;
use Exception;

class SteamUser
{
    /**
     * Steam Community URL using 64bit steamid
     *
     * @var string
     */
    const STEAM_PROFILE = 'https://steamcommunity.com/profiles/%s';

    /**
     * Steam Community URL using custom id
     *
     * @var string
     */
    const STEAM_PROFILE_ID = 'https://steamcommunity.com/id/%s';

    /**
     * Steam API GetPlayerSummaries URL
     *
     * @var string
     */
    const STEAM_PLAYER_API = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

    /**
     * SteamID - 765611XXXXXXXXXXX
     *
     * @var int|string
     */
    public $steamid;

    /**
     * SteamID2 - STEAM_X:Y:Z
     *
     * @var string
     */
    public $steamid2;

    /**
     * SteamID3 - [U:1:X]
     *
     * @var string
     */
    public $steamid3;

    /**
     * Player's small avatar 32x32
     *
     * @var string
     */
    public $avatarSmall;

    /**
     * Player's medium avatar 64x64
     *
     * @var string
     */
    public $avatarMedium;

    /**
     * Player's medium avatar 184x184
     *
     * @var string
     */
    public $avatarLarge;

    /**
     * Player's display name
     *
     * @var string
     */
    public $name;

    /**
     * Player's realname set
     *
     * @var string|null
     */
    public $realName;

    /**
     * Player's online state
     *
     * @var string
     */
    public $playerState;

    /**
     * Player's status message
     *
     * @var string
     */
    public $stateMessage;

    /**
     * Player's privacy setting
     *
     * @var string
     */
    public $privacyState;

    /**
     * Player's privacy setting
     *
     * @var int
     */
    public $visibilityState;

    /**
     * Player's profile URL
     */
    public $profileURL;

    /**
     * Epoch timestamp of joining steam
     *
     * @var int|null
     */
    public $joined;


    /**
     * personastates
     */
    private static $personastates = [
        'Offline',
        'Online',
        'Busy',
        'Away',
        'Snooze',
        'Looking to trade',
        'Looking to play',
    ];

    /**
     * SteamUser constructor.
     *
     * @param string $steamid
     */
    public function __construct($steamid)
    {
        $this->steamid = $steamid;
        $this->profileURL = sprintf(self::STEAM_PROFILE, $steamid);
        $this->convertID();
    }

    /**
     * Retrieve a player's profile details using steam api or profile xml
     *
     * @return $this
     */
    public function getPlayerInfo()
    {
        $this->userInfo();
        return $this;
    }

    /**
     * Convert steamm id 64 bit to other variants
     */
    private function convertID()
    {
        $x = ($this->steamid >> 56) & 0xFF;
        $y = Config::get('steam-login.universe') ? $this->steamid & 1 : 0;
        $z = ($this->steamid >> 1) & 0x7FFFFFF;

        $this->steamid2 = "STEAM_$x:$y:$z";
        $this->steamid3 = "[U:1:".($z * 2 +$y)."]";
    }

    /**
     * Retrive a player's profile info from Steam
     *
     * @throws Exception
     */
    private function userInfo()
    {
        $method = in_array(Config::get('steam-login.method'), ['api', 'xml']) ? Config::get('steam-login.method') : 'xml';

        switch ($method) {
            case 'api':
                $data = json_decode(SteamLogin::curl(sprintf(self::STEAM_PLAYER_API, Config::get('steam-login.api_key'), $this->steamid)));
                $data = isset($data->response->players[0]) ? $data->response->players[0] : [];

                $length = count((array)$data);

                if ($length > 0) {
                    $this->name = $data->personaname;
                    $this->realName = !empty($data->realname) ? $data->realname : null;
                    $this->playerState = $data->personastate != 0 ? 'Online' : 'Offline';
                    $this->privacyState = ($data->communityvisibilitystate == 1 || $data->communityvisibilitystate == 2) ? 'Private' : 'Public';
                    $this->stateMessage = isset(self::$personastates[$data->personastate]) ? self::$personastates[$data->personastate] : $data->personastate;
                    $this->visibilityState = $data->communityvisibilitystate;
                    $this->avatarSmall = $data->avatar;
                    $this->avatarMedium = $data->avatarmedium;
                    $this->avatarLarge = $data->avatarfull;
                    $this->joined = isset($data->timecreated) ? $data->timecreated : null;
                }
                break;
            case 'xml':
                $data = simplexml_load_string(SteamLogin::curl(sprintf(self::STEAM_PROFILE.'/?xml=1', $this->steamid)), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($data !== false && !isset($data->error)) {
                    $this->name = (string) $data->steamID;
                    $this->realName = !empty($data->realName) ? $data->realName : null;
                    $this->playerState = ucfirst($data->onlineState);
                    $this->privacyState = ($data->privacyState == 'friendsonly' || $data->privacyState == 'private') ? 'Private' : 'Public';
                    $this->stateMessage = (string) $data->stateMessage;
                    $this->visibilityState = (int) $data->visibilityState;
                    $this->avatarSmall = (string) $data->avatarIcon;
                    $this->avatarMedium = (string) $data->avatarMedium;
                    $this->avatarLarge = (string) $data->avatarFull;
                    $this->joined = isset($data->memberSince) ? strtotime($data->memberSince) : null;
                } else {
                    if (env('APP_DEBUG')) {
                        throw new Exception('No XML data: '.(isset($data['error']) ? $data['error'] : 'please look into this'));
                    }
                }
                break;
            default:
                break;
        }
    }

}