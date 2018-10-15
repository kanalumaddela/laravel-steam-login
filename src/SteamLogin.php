<?php

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;

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
     * @var string
     */
    protected $previousPage;

    /**
     * Login route to redirect to steam.
     *
     * @var string
     */
    protected $loginRoute;

    /**
     * Auth handle route after returning from steam.
     *
     * @var string
     */
    protected $authRoute;

    /**
     * Laravel Container/Application.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Laravel Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

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
     * Defines if app is HTTPS.
     *
     * @var bool
     */
    protected $https;

    /**
     * Login URL.
     *
     * @var string
     */
    private $loginUrl;

    /**
     * SteamLogin constructor.
     *
     * @param $app
     *
     * @throws Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->guzzle = new GuzzleClient();
        $this->https = $this->request->server('HTTP_X_FORWARDED_PROTO') === 'https' ?? isset($_SERVER['https']);

        $previousPage = url()->previous();
        $this->loginRoute = route(Config::get('steam-login.routes.login'));
        $this->authRoute = route(Config::get('steam-login.routes.auth'));

        $this->previousPage = $this->validRequest() && $this->request->has('redirect') ? $this->request->query('redirect') : ($previousPage != $this->loginRoute && $previousPage != $this->authRoute ? $previousPage : url('/'));

        if (!filter_var($this->previousPage, FILTER_VALIDATE_URL)) {
            throw new Exception('previousPage is not valid url');
        }

        $this->setReturnUrl($this->authRoute.'?redirect='.$previousPage);
    }

    /**
     * Check if query parameters are valid post steam login.
     *
     * @return bool
     */
    protected function validRequest(): bool
    {
        $params = [
            'openid_assoc_handle',
            'openid_claimed_id',
            'openid_sig',
            'openid_signed',
        ];

        return $this->request->filled($params);
    }

    /**
     * Generate openid login URL with specified return.
     *
     * @param $return
     */
    public function setReturnUrl(string $return)
    {
        $this->loginUrl = $this->createLoginURL($return);
    }

    /**
     * Build the steam openid login URL.
     *
     * @param string|null $return
     *
     * @return string
     */
    public function createLoginUrl(?string $return = null): string
    {
        $params = [
            'openid.ns'         => self::OPENID_SPECS,
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => (!empty($return) ? $return : $this->authRoute),
            'openid.realm'      => ($this->https ? 'https' : 'http').'://'.$this->request->getHttpHost(),
            'openid.identity'   => self::OPENID_SPECS.'/identifier_select',
            'openid.claimed_id' => self::OPENID_SPECS.'/identifier_select',
        ];

        return self::OPENID_STEAM.'?'.http_build_query($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginURL(): string
    {
        return $this->loginUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function redirectToSteam(): RedirectResponse
    {
        return redirect($this->loginUrl);
    }

    /**
     * Return the user to the page they were on before logging in.
     *
     * @return RedirectResponse
     */
    public function previousPage(): RedirectResponse
    {
        return redirect($this->previousPage);
    }

    /**
     * Return player object and optionally choose to retrieve profile info.
     *
     * @param bool $info
     *
     * @throws Exception
     *
     * @return SteamUser
     */
    public function getPlayer(bool $info = false): SteamUser
    {
        return $info ? $this->player->getUserInfo() : $this->player;
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

    /**
     * Check if login is valid.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validated(): bool
    {
        if (!$this->validRequest()) {
            if ($this->request->has(openid_error)) {
                throw new Exception($this->request->input('openid_error'));
            }

            return false;
        }

        $steamid = $this->validate();

        if ($validated = !empty($steamid)) {
            $this->player = new SteamUser($steamid);
        }

        return $validated;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): ?string
    {
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

        $this->response = $this->guzzle->post(self::OPENID_STEAM, [
            'connect_timeout' => Config::get('steam-login.timeout'),
            'form_params'     => $params,
        ]);

        $result = $this->response->getBody();

        preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $this->request->input('openid_claimed_id'), $matches);
        $steamid = is_numeric($matches[1]) ? $matches[1] : 0;
        $steamid = preg_match("#is_valid\s*:\s*true#i", $result) === 1 ? (int) $steamid : null;

        return $steamid;
    }

    /**
     * Returns Steam Login button with link.
     *
     * @param string $type
     *
     * @return string
     */
    public function loginButton(string $type = 'small'): string
    {
        return sprintf('<a href="%s"><img src="%s" /></a>', $this->loginUrl, self::button($type));
    }

    /**
     * Return the URL of Steam Login buttons.
     *
     * @param string $type
     *
     * @return string
     */
    public static function button(string $type = 'small'): string
    {
        return 'https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_0'.($type === 'small' ? 1 : 2).'.png';
    }
}
