<?php

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use GuzzleHttp\Client as GuzzleClient;

class SteamLogin implements SteamLoginInterface
{
    /**
     * Steam OpenID URL.
     *
     * @var string
     */
    const OPENID_STEAM = 'https://steamcommunity.com/openid/login';

    /**
     * OpenID Specs.
     *
     * @var string
     */
    const OPENID_SPECS = 'http://specs.openid.net/auth/2.0';

    /**
     * SteamUser instance of player details.
     *
     * @var SteamUser
     */
    public $player;

    /**
     * Login URL.
     *
     * @var string
     */
    private $loginUrl;

    /**
     * Steam Auth related routes.
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var string
     */
    protected $previousPage;

    /**
     * Laravel Container/Application
     *
     * @var \Illuminate\Http\Request
     */
    protected $app;

    /**
     * Laravel Request instance.
     *
     * @var \Illuminate\Http\Request $request
     */
    protected $request;

    /**
     * Guzzle instance
     *
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * Defines if app is HTTPS
     *
     * @var boolean
     */
    protected $https;

    /**
     * SteamLogin constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->guzzle = new GuzzleClient();
        $this->https = $this->request->server('HTTP_X_FORWARDED_PROTO') == 'https' ?? isset($_SERVER['https']);

        $this->previousPage = url()->previous();
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }

        throw new Exception('Undefined property :'.$name);
    }

    /**
     * Return login URL.
     *
     * @return string
     */
    public function getLoginURL(): string
    {
        return $this->loginUrl;
    }

    /**
     * Generate openid login URL with specified return.
     *
     * @param $return
     *
     * @return string
     */
    public function loginURL($return): string
    {
        return $this->createLoginURL($return);
    }

    /**
     * Return player object and optionally choose to retrieve profile info.
     *
     * @param bool $info
     *
     * @return SteamUser
     * @throws Exception
     */
    public function getPlayer($info = false): SteamUser
    {
        return $info ? $this->player->getUserInfo() : $this->player;
    }

    public function redirectToSteam()
    {
        return redirect($this->loginUrl);
    }

    /**
     * Returns Steam Login button with link.
     *
     * @param string $type
     *
     * @return string
     */
    public function loginButton($type = 'small'): string
    {
        return sprintf('<a href="%s"><img src="%s" /></a>', $this->loginUrl, self::button($type));
    }

    /**
     * Check if login is valid.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function valid(): bool
    {
        if (!$this->validRequest()) {
            return false;
        }

        $steamid = $this->validate();

        if (is_null($steamid) && env('APP_DEBUG')) {
            throw new RuntimeException('Steam Auth failed or timed out');
        }
        if ($steamid) {
            $this->player = new SteamUser($steamid);
        }

        return !is_null($steamid);
    }

    /**
     * Return the URL of Steam Login buttons.
     *
     * @param string $type
     *
     * @return string
     */
    public static function button($type = 'small'): string
    {
        return 'https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_0'.($type == 'small' ? 1 : 2).'.png';
    }

    /**
     * Build the steam openid login URL.
     *
     * @param null $return
     *
     * @return string
     */
    private function createLoginUrl($return = null): string
    {
        $params = [
            'openid.ns'         => self::OPENID_SPECS,
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => (!empty($return) ? $return : $this->routes[0]),
            'openid.realm'      => ($this->https ? 'https' : 'http').'://'.$this->request->getHttpHost(),
            'openid.identity'   => self::OPENID_SPECS.'/identifier_select',
            'openid.claimed_id' => self::OPENID_SPECS.'/identifier_select',
        ];

        return self::OPENID_STEAM.'?'.http_build_query($params);
    }

    /**
     * Validate Steam Login.
     *
     * @throws Exception
     *
     * @return string|int|null
     */
    public function validate()
    {
        try {
            $params = [
                'openid.assoc_handle' => $this->request->input('openid_assoc_handle'),
                'openid.signed'       => $this->request->input('openid_signed'),
                'openid.sig'          => $this->request->input('openid_sig'),
                'openid.ns'           => self::OPENID_SPECS,
            ];

            $signed = explode(',', $this->request->input('openid_signed'));

            foreach ($signed as $item) {
                $params['openid.'.$item] = $this->request->input('openid_'.str_replace('.', '_', $item));
            }

            $params['openid.mode'] = 'check_authentication';

            $response = $this->guzzle->post(self::OPENID_STEAM, [
                'connect_timeout' => Config::get('steam-login.timeout'),
                'form_params' => $params
            ]);

            $result = $response->getBody();
            dump($result);

            preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $this->request->input('openid_claimed_id'), $matches);
            $steamid = is_numeric($matches[1]) ? $matches[1] : 0;
            $steamid = preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamid : null;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                throw $e;
            }
            $steamid = null;
        }

        return $steamid;
    }

    /**
     * Check if query paramters are valid post steam login.
     *
     * @return bool
     */
    private function validRequest()
    {
        $params = [
            'openid_assoc_handle',
            'openid_claimed_id',
            'openid_sig',
            'openid_signed',
        ];

        return $this->request->filled($params);
    }
}
