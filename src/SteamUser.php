<?php

namespace kanalumaddela\LaravelSteamLogin;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Fluent;
use SteamID;

class SteamUser extends SteamID
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
     * Fluent instance of player data.
     *
     * @var \Illuminate\Support\Fluent
     */
    public $fluent;

    /**
     * SteamID - 765611XXXXXXXXXXX.
     *
     * @var int
     */
    public $steamid;

    /**
     * SteamID2 - STEAM_X:Y:Z.
     *
     * @var string
     */
    public $steamid2;

    /**
     * SteamID3 - [U:1:W].
     * W = Z*2+Y.
     *
     * @var string
     */
    public $steamid3;

    /**
     * Steam AccountID - W.
     *
     * @var int
     */
    public $accountId;

    /**
     * Player's small avatar 32x32.
     *
     * @var string
     */
    public $avatarSmall;

    /**
     * Player's medium avatar 64x64.
     *
     * @var string
     */
    public $avatarMedium;

    /**
     * Player's medium avatar 184x184.
     *
     * @var string
     */
    public $avatarLarge;

    /**
     * Player's display name.
     *
     * @var string
     */
    public $name;

    /**
     * Player's realname set.
     *
     * @var string|null
     */
    public $realName;

    /**
     * Player's online state.
     *
     * @var string
     */
    public $playerState;

    /**
     * Player's status message.
     *
     * @var string
     */
    public $stateMessage;

    /**
     * Player's privacy setting.
     *
     * @var string
     */
    public $privacyState;

    /**
     * Player's privacy setting.
     *
     * @var int
     */
    public $visibilityState;

    /**
     * Player's profile URL.
     * Can be either:
     *   - https://steamcommunity.com/profiles/<steamid>
     *   - https://steamcommunity.com/profiles/[U:1:W]
     *   - https://steamcommunity.com/id/<name>.
     */
    public $profileURL;

    /**
     * Epoch timestamp of joining steam.
     *
     * @var int|null
     */
    public $joined;

    /**
     * Profile data retrieval method to use.
     *
     * @var string
     */
    protected $method = 'xml';

    /**
     * URL to use when retrieving a player's profile.
     *
     * @var string
     */
    protected $profileDataUrl;

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
        parent::__construct($steamid);

        $this->guzzle = $guzzle ?? new GuzzleClient();

        $this->steamid = $this->ConvertToUInt64();
        $this->steamid2 = $this->RenderSteam2();
        $this->steamid3 = $this->RenderSteam3();
        $this->accountId = $this->GetAccountID();

        $this->method = Config::get('steam-login.method', 'xml') == 'api' ? 'api' : 'xml';
        $this->profileURL = sprintf(self::STEAM_PROFILE, $this->steamid3);
        $this->profileDataUrl = $this->method == 'xml' ? sprintf(self::STEAM_PROFILE.'/?xml=1', $this->steamid) : sprintf(self::STEAM_PLAYER_API, Config::get('steam-login.api_key'), $this->steamid);
    }

    public function __toString()
    {
        return 'todo';
    }

    public function getUserInfo()
    {
        $this->userInfo();

        return $this;
    }

    /**
     * Retrieve a player's profile info from Steam via API or XML data.
     */
    private function userInfo()
    {
        $response = $this->guzzle->get($this->profileDataUrl, ['connect_timeout' => Config::get('steam-login.timeout')]);
        $data = $this->method == 'xml' ? simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA) : json_decode($response->getBody());

        switch ($this->method) {
            case 'api':
                $data = isset($data->response->players[0]) ? $data->response->players[0] : null;

                if ($data) {
                    $this->name = $data->personaname;
                    $this->realName = !empty($data->realname) ? $data->realname : null;
                    $this->playerState = $data->personastate != 0 ? 'Online' : 'Offline';
                    $this->privacyState = ($data->communityvisibilitystate == 1 || $data->communityvisibilitystate == 2) ? 'Private' : 'Public';
                    $this->stateMessage = isset(self::$personaStates[$data->personastate]) ? self::$personaStates[$data->personastate] : $data->personastate;
                    $this->visibilityState = $data->communityvisibilitystate;
                    $this->avatarSmall = $data->avatar;
                    $this->avatarMedium = $data->avatarmedium;
                    $this->avatarLarge = $data->avatarfull;
                    $this->joined = isset($data->timecreated) ? $data->timecreated : null;
                }
                break;
            case 'xml':
                if ($data !== false && !isset($data->error)) {
                    $this->name = (string) $data->steamID;
                    $this->realName = !empty($data->realName) ? $data->realName : null;
                    $this->playerState = ucfirst($data->onlineState);
                    $this->privacyState = ($data->privacyState == 'friendsonly' || $data->privacyState == 'private') ? 'Private' : 'Public';
                    $this->stateMessage = (string) $data->stateMessage == 'Last Online ' ? 'Last Online: Unknown' : $data->stateMessage;
                    $this->visibilityState = (int) $data->visibilityState;
                    $this->avatarSmall = (string) $data->avatarIcon;
                    $this->avatarMedium = (string) $data->avatarMedium;
                    $this->avatarLarge = (string) $data->avatarFull;
                    $this->joined = isset($data->memberSince) ? strtotime($data->memberSince) : null;
                }
                break;
            default:
                break;
        }
    }
}
