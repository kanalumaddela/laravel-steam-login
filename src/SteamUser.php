<?php

namespace kanalumaddela\LaravelSteamLogin;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Fluent;
use SteamID;

class SteamUser
{
    /**
     * Steam Community URL using 64bit steamid.
     *
     * @var string
     */
    const STEAM_PROFILE = 'https://steamcommunity.com/profiles/%s';

    /**
     * Steam Community URL using custom id.
     *
     * @var string
     */
    const STEAM_PROFILE_ID = 'https://steamcommunity.com/id/%s';

    /**
     * Steam API GetPlayerSummaries URL.
     *
     * @var string
     */
    const STEAM_PLAYER_API = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

    /**
     * Attributes of a user. e.g. steamid, profile, etc.
     *
     * @var \stdClass
     */
    public $attributes;

    /**
     * Fluent instance of user data.
     *
     * @var \Illuminate\Support\Fluent
     */
    public $fluent;

    /**
     * Profile data retrieval method to use.
     *
     * @var string
     */
    protected $method = 'xml';

    /**
     * URL to use when retrieving a user's profile.
     *
     * @var string
     */
    protected $profileDataUrl;

    /**
     * Guzzle instance.
     *
     * @var \SteamID
     */
    protected $steamId;

    /**
     * Guzzle instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * personastates.
     */
    private static $personaStates = [
        'Offline',
        'Online',
        'Busy',
        'Away',
        'Snooze',
        'Looking to trade',
        'Looking to play',
    ];

    /**
     * SteamUser constructor. Extends SteamID and constructs that first.
     *
     * @param string            $steamid
     * @param GuzzleClient|null $guzzle
     */
    public function __construct($steamid, GuzzleClient $guzzle = null)
    {
        $this->steamId = new SteamID($steamid);
        $this->guzzle = $guzzle ?? new GuzzleClient();

        $this->attributes = new \stdClass();

        $this->attributes->steamid = $this->steamId->ConvertToUInt64();
        $this->attributes->steamid2 = $this->steamId->RenderSteam2();
        $this->attributes->steamid3 = $this->steamId->RenderSteam3();
        $this->attributes->accountId = $this->steamId->GetAccountID();
        $this->attributes->profileUrl = sprintf(self::STEAM_PROFILE, $this->attributes->steamid3);
        $this->attributes->profileDataUrl = sprintf(self::STEAM_PROFILE.'/?xml=1', $this->attributes->steamid);

        $this->fluent = new Fluent($this->attributes);

        $this->method = Config::get('steam-login.method', 'xml') == 'api' ? 'api' : 'xml';
        $this->profileDataUrl = $this->method == 'xml' ? $this->attributes->profileDataUrl : sprintf(self::STEAM_PLAYER_API, Config::get('steam-login.api_key'), $this->attributes->steamid);
    }

    /**
     * magic methiod __call.
     *
     * @param $name
     * @param $arguments
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->fluent, $name)) {
            return call_user_func_array([$this->fluent, $name], $arguments);
        }
        if (method_exists($this->steamId, $name)) {
            return call_user_func_array([$this->steamId, $name], $arguments);
        }
        if (substr($name, 0, 3) === 'get') {
            $property = lcfirst(substr($name, 3));

            return call_user_func_array([$this, '__get'], [$property]);
        }

        throw new \Exception('Unknown method '.$name);
    }

    /**
     * magic method __get.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->fluent->__get($name);
    }

    /**
     * magic method __toString using Fluent toJson().
     *
     * @return string
     */
    public function __toString()
    {
        return $this->fluent->toJson();
    }

    /**
     * Retrieve a user's steam info set its attributes.
     *
     * @return $this
     */
    public function getUserInfo()
    {
        $this->userInfo();

        return $this;
    }

    /**
     * Retrieve a user's profile info from Steam via API or XML data.
     */
    private function userInfo()
    {
        $response = $this->guzzle->get($this->profileDataUrl, ['connect_timeout' => Config::get('steam-login.timeout')]);
        $data = $this->method == 'xml' ? simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA) : json_decode($response->getBody());

        switch ($this->method) {
            case 'api':
                $data = isset($data->response->players[0]) ? $data->response->players[0] : null;

                if ($data) {
                    $this->attributes->name = $data->personaname;
                    $this->attributes->realName = !empty($data->realname) ? $data->realname : null;
                    $this->attributes->playerState = $data->personastate != 0 ? 'Online' : 'Offline';
                    $this->attributes->privacyState = ($data->communityvisibilitystate == 1 || $data->communityvisibilitystate == 2) ? 'Private' : 'Public';
                    $this->attributes->stateMessage = isset(self::$personaStates[$data->personastate]) ? self::$personaStates[$data->personastate] : $data->personastate;
                    $this->attributes->visibilityState = $data->communityvisibilitystate;
                    $this->attributes->avatarSmall = $data->avatar;
                    $this->attributes->avatarMedium = $data->avatarmedium;
                    $this->attributes->avatarLarge = $data->avatarfull;
                    $this->attributes->joined = isset($data->timecreated) ? $data->timecreated : null;
                }
                break;
            case 'xml':
                if ($data !== false && !isset($data->error)) {
                    $this->attributes->name = (string) $data->steamID;
                    $this->attributes->realName = !empty($data->realName) ? $data->realName : null;
                    $this->attributes->playerState = ucfirst($data->onlineState);
                    $this->attributes->privacyState = ($data->privacyState == 'friendsonly' || $data->privacyState == 'private') ? 'Private' : 'Public';
                    $this->attributes->stateMessage = (string) $data->stateMessage == 'Last Online ' ? 'Last Online: Unknown' : $data->stateMessage;
                    $this->attributes->visibilityState = (int) $data->visibilityState;
                    $this->attributes->avatarSmall = (string) $data->avatarIcon;
                    $this->attributes->avatarMedium = (string) $data->avatarMedium;
                    $this->attributes->avatarLarge = (string) $data->avatarFull;
                    $this->attributes->joined = isset($data->memberSince) ? strtotime($data->memberSince) : null;
                }
                break;
            default:
                break;
        }

        $this->fluent = new Fluent($this->attributes);
    }
}
