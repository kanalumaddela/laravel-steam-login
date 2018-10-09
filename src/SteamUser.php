<?php

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Fluent;
use SteamID;

class SteamUser
{
    /**
     * Steam Community URL using 64bit steamId.
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
     * personaStates.
     */
    protected static $personaStates = [
        'Offline',
        'Online',
        'Busy',
        'Away',
        'Snooze',
        'Looking to trade',
        'Looking to play',
    ];

    /**
     * Attributes of a user. e.g. steamId, profile, etc.
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
     * xPaw instance.
     *
     * @var \SteamID
     */
    protected $xPawSteamId;

    /**
     * Guzzle instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * Guzzle response.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * SteamUser constructor. Extends SteamID and constructs that first.
     *
     * @param string|int        $steamId
     * @param GuzzleClient|null $guzzle
     */
    public function __construct($steamId, GuzzleClient $guzzle = null)
    {
        $this->xPawSteamId = new SteamID($steamId);
        $this->guzzle = $guzzle ?? new GuzzleClient();

        $this->attributes = new \stdClass();

        $this->attributes->steamId = $this->steamId->ConvertToUInt64();
        $this->attributes->steamId2 = $this->steamId->RenderSteam2();
        $this->attributes->steamId3 = $this->steamId->RenderSteam3();
        $this->attributes->accountId = $this->steamId->GetAccountID();
        $this->attributes->accountUrl = sprintf(self::STEAM_PROFILE, $this->attributes->steamId3);
        $this->attributes->profileDataUrl = sprintf(self::STEAM_PROFILE.'/?xml=1', $this->attributes->steamId);

        $this->fluent = new Fluent($this->attributes);

        $this->method = Config::get('steam-login.method', 'xml') === 'api' ? 'api' : 'xml';
        $this->profileDataUrl = $this->method === 'xml' ? $this->attributes->profileDataUrl : sprintf(self::STEAM_PLAYER_API, Config::get('steam-login.api_key'), $this->attributes->steamId);
    }

    /**
     * magic method __call.
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
        if (method_exists($this->xPawSteamId, $name)) {
            return call_user_func_array([$this->xPawSteamId, $name], $arguments);
        }
        if (substr($name, 0, 3) === 'get') {
            $property = lcfirst(substr($name, 3));

            return call_user_func_array([$this, '__get'], [$property]);
        }

        throw new Exception('Unknown method '.$name);
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
    public function __toString(): string
    {
        return $this->fluent->toJson();
    }

    /**
     * Retrieve a user's steam info set its attributes.
     *
     * @return $this
     */
    public function getUserInfo(): self
    {
        $this->userInfo();

        return $this;
    }

    /**
     * Retrieve a user's profile info from Steam via API or XML data.
     */
    private function userInfo()
    {
        $this->response = $this->guzzle->get($this->profileDataUrl, ['connect_timeout' => Config::get('steam-login.timeout')]);
        $data = $this->method === 'xml' ? simplexml_load_string($this->response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA) : json_decode($this->response->getBody());

        switch ($this->method) {
            case 'api':
                $data = isset($data->response->players[0]) ? $data->response->players[0] : null;

                if ($data) {
                    $this->attributes->name = $data->personaname;
                    $this->attributes->realName = isset($data->realname) ? $data->realname : null;
                    $this->attributes->profileUrl = $data->profileurl;
                    $this->attributes->privacyState = $data->communityvisibilitystate === 3 ? 'Public' : 'Private';
                    $this->attributes->visibilityState = $data->communityvisibilitystate;
                    $this->attributes->isOnline = $data->personastate != 0;
                    $this->attributes->onlineState = isset($data->gameid) ? 'In-Game' : ($data->personastate != 0 ? 'Online' : 'Offline');
                    // todo: stateMessage
                    $this->attributes->avatarSmall = $this->attributes->avatarIcon = $data->avatar;
                    $this->attributes->avatarMedium = $data->avatarmedium;
                    $this->attributes->avatarLarge = $this->attributes->avatarFull = $this->attributes->avatar = $data->avatarfull;
                    $this->attributes->joined = isset($data->timecreated) ? $data->timecreated : null;
                }
                break;
            case 'xml':
                if ($data !== false && !isset($data->error)) {
                    $this->attributes->name = (string) $data->steamID;
                    $this->attributes->realName = isset($data->realName) ? $data->realName : null;
                    $this->attributes->profileUrl = isset($data->customURL) ? 'https://steamcommunity.com/id/'.$data->customURL : $this->attributes->accountUrl;
                    $this->attributes->privacyState = $data->privacyState === 'public' ? 'Public' : 'Private';
                    $this->attributes->visibilityState = (int) $data->visibilityState;
                    $this->attributes->isOnline = $data->onlineState != 'offline';
                    $this->attributes->onlineState = $data->onlineState === 'in-game' ? 'In-Game' : ucfirst($data->onlineState);
                    // todo: stateMessage
                    $this->attributes->avatarSmall = $this->attributes->avatarIcon = (string) $data->avatarIcon;
                    $this->attributes->avatarMedium = (string) $data->avatarMedium;
                    $this->attributes->avatarLarge = $this->attributes->avatarFull = $this->attributes->avatar = (string) $data->avatarFull;
                    $this->attributes->joined = isset($data->memberSince) ? strtotime($data->memberSince) : null;
                }
                break;
            default:
                break;
        }

        $this->fluent = new Fluent($this->attributes);
    }

    /**
     * Return Guzzle response of POSTing to Steam's OpenID.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
