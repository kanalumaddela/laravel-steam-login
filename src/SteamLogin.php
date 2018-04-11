<?php

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use RuntimeException;

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
    private $loginURL;

    /**
     * Steam Auth related routes.
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var string
     */
    protected $original_page;

    /**
     * Laravel Request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * SteamLogin constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->player = new \stdClass();

        $this->routes = [
            'callback' => Config::get('steam-login.routes.callback') != Config::get('steam-login.routes.login') ? url(Config::get('steam-login.routes.callback')) : url('/auth/steam'),
            'login'    => Config::get('steam-login.routes.login') != Config::get('steam-login.routes.callback') ? url(Config::get('steam-login.routes.login')) : url('/login/steam'),
        ];

        if (Config::get('steam-login.method') == 'api') {
            if (empty(Config::get('steam-login.api_key'))) {
                throw new RuntimeException('Steam API not defined, please set it in your .env or in config/steam-login.php');
            }
        }

        $this->original_page = url()->previous() != url()->current() && url()->previous() != $this->routes['callback'] && url()->previous() != $this->routes['login'] ? url()->previous() : url('/');

        $this->loginURL = $this->createLoginURL($this->routes['callback'].'?redirect='.$this->original_page);
    }

    /**
     * Return login URL.
     *
     * @return string
     */
    public function getLoginURL(): string
    {
        return $this->loginURL;
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
     */
    public function getPlayer($info = false): SteamUser
    {
        return $info ? $this->player->getPlayerInfo() : $this->player;
    }

    /**
     * Redirect to steam.
     */
    public function redirect()
    {
        return redirect($this->loginURL);
    }

    /**
     * Redirect back to their original page.
     */
    public function originalPage()
    {
        $original_page = $this->request->input('redirect', null);

        return redirect($original_page != $this->routes['login'] && $original_page != $this->routes['callback'] ? $original_page : url('/'));
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
        return sprintf('<a href="%s"><img src="%s" /></a>', $this->loginURL, self::button($type));
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
     * Simple cURL GET.
     *
     * @param string
     *
     * @return string
     */
    public static function curl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    /**
     * Build the steam openid login URL.
     *
     * @param null $return
     *
     * @return string
     */
    private function createLoginURL($return = null): string
    {
        $params = [
            'openid.ns'         => self::OPENID_SPECS,
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => (!empty($return) ? $return : $this->routes['callback']),
            'openid.realm'      => $this->request->getSchemeAndHttpHost(),
            'openid.identity'   => self::OPENID_SPECS.'/identifier_select',
            'openid.claimed_id' => self::OPENID_SPECS.'/identifier_select',
        ];

        return self::OPENID_STEAM.'?'.http_build_query($params);
    }

    /**
     * Check if query paramters are valid post steam login.
     *
     * @return bool
     */
    private function validRequest()
    {
        return $this->request->has('openid_assoc_handle') && $this->request->has('openid_claimed_id') && $this->request->has('openid_sig') && $this->request->has('openid_signed');
    }

    /**
     * Validate Steam Login.
     *
     * @throws Exception
     *
     * @return string|int|null
     */
    private function validate()
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
                $value = $this->request->input('openid_'.str_replace('.', '_', $item));
                $params['openid.'.$item] = get_magic_quotes_gpc() ? stripslashes($value) : $value;
            }

            $params['openid.mode'] = 'check_authentication';

            $data = http_build_query($params);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Accept-language: en',
                'Content-type: application/x-www-form-urlencoded',
                'Content-Length: '.strlen($data),
            ]);

            curl_setopt($curl, CURLOPT_URL, self::OPENID_STEAM);
            $result = curl_exec($curl);
            curl_close($curl);

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
}
