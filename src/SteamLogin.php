<?php
/**
 * Laravel Steam Login.
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use kanalumaddela\LaravelSteamLogin\Contracts\SteamLoginInterface;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;
use function config;
use function explode;
use function filter_var;
use function get_class;
use function http_build_query;
use function in_array;
use function is_numeric;
use function parse_url;
use function preg_match;
use function redirect;
use function route;
use function sprintf;
use function str_replace;
use function strpos;
use function trigger_error;
use function url;

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
     * Is this a Laravel application?
     *
     * @var bool
     */
    protected static $isLaravel = true;

    /**
     * Defines if app is HTTPS.
     *
     * @var bool
     */
    protected static $isHttps;

    /**
     * The app's original http scheme in case of reverse proxy with ssl.
     *
     * @var string
     */
    protected static $originalScheme;

    /**
     * Default OpenID form params.
     *
     * @var array
     */
    protected static $openIdParams = [
        'openid.ns'         => self::OPENID_SPECS,
        'openid.mode'       => 'checkid_setup',
        'openid.identity'   => self::OPENID_SPECS.'/identifier_select',
        'openid.claimed_id' => self::OPENID_SPECS.'/identifier_select',
    ];

    /**
     * Laravel/Lumen Application/Container.
     *
     * @var \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Request instance.
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
     * OpenID body response.
     *
     * @var string
     */
    protected $openIdResponse;

    /**
     * OpenID realm.
     *
     * @var string
     */
    protected $realm;

    /**
     * Login route to redirect to steam.
     *
     * @var string
     */
    protected $loginRoute;

    /**
     * Auth handle route for openid.return_to.
     *
     * @var string
     */
    protected $authRoute;

    /**
     * ?redirect parameter used for automatic handling to the previous page a user was on.
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Login URL to Steam.
     *
     * @var string
     */
    protected $loginUrl;

    /**
     * SteamUser instance of player details.
     *
     * @var SteamUser
     */
    protected $steamUser;

    /**
     * SteamLoginFixed constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Container\Container $app
     */
    public function __construct($app = null)
    {
        if (PHP_INT_SIZE !== 8) {
            trigger_error('64-bit PHP is required to convert steamids', E_USER_WARNING);
        }

        $this->app = $app;
        $this->request = $app->get('request');
        self::$isLaravel = strpos(get_class($app), 'Lumen') === false;
        self::$isHttps = $this->request->server('HTTPS', 'off') !== 'off' || $this->request->server('SERVER_PORT') == 443 || $this->request->server('HTTP_X_FORWARDED_PROTO') === 'https';
        self::$originalScheme = $this->request->getScheme();

        $this->setGuzzle(new GuzzleClient())->setRealm();
    }

    /**
     * Build the login url with optional openid.return_to and ?redirect.
     *
     * @param string|null $return
     * @param string|null $redirectTo
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function buildLoginUrl(?string $return = null, ?string $redirectTo = null): string
    {
        if (self::$isHttps && !$this->request->isSecure() && self::$originalScheme !== 'https') {
            $this->app->get('url')->forceScheme('https');
        }

        $this->loginRoute = route(config('steam-login.routes.login'));
        $this->authRoute = route(config('steam-login.routes.auth'));

        $this->app->get('url')->forceScheme(self::$originalScheme);

        if (empty($return) && !empty($this->authRoute)) {
            $return = $this->authRoute;
        }

        if (empty($return) && empty($this->authRoute)) {
            $this->authRoute = $return = route(config('steam-login.routes.auth'));
        }

        $this->setRedirectTo($redirectTo);

        $params = self::$openIdParams;
        $this->realm = $this->getRealm();

        if (parse_url($this->realm, PHP_URL_HOST) !== parse_url($return, PHP_URL_HOST)) {
            throw new InvalidArgumentException(sprintf('realm: `%s` and return_to: `%s` do not have matching hosts', $this->realm, $return));
        }

        $params['openid.realm'] = $this->realm;
        $params['openid.return_to'] = $return.(self::$isLaravel && !empty($this->redirectTo) ? '?'.http_build_query(['redirect' => $this->redirectTo]) : '');

        return self::OPENID_STEAM.'?'.http_build_query($params);
    }

    /**
     * @param string|null $return
     *
     * @return string
     *
     * @deprecated
     */
    public function createLoginUrl(?string $return = null): string
    {
        return $this->buildLoginUrl($return);
    }

    /**
     * @param string $redirectTo
     *
     * @throws InvalidArgumentException
     *
     *@return \kanalumaddela\LaravelSteamLogin\SteamLogin
     */
    public function setRedirectTo(string $redirectTo = null): self
    {
        if (empty($redirectTo)) {
            $redirectTo = url()->previous();
        }

        if (in_array($redirectTo, [$this->loginRoute, $this->authRoute])) {
            $redirectTo = url('/');
        } elseif (!filter_var($redirectTo, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('$redirectTo: `'.$redirectTo.'` is not a valid URL');
        }

        $this->redirectTo = $redirectTo;

        return $this;
    }

    /**
     * Return the ?redirect URL.
     *
     * @return string
     */
    public function getRedirectTo(): string
    {
        if ($this->request->has('redirect')) {
            $this->setRedirectTo($this->request->query('redirect'));
        } elseif (empty($this->redirectTo)) {
            $this->setRedirectTo();
        }

        return $this->redirectTo;
    }

    /**
     * Set the Guzzle instance to use.
     *
     * @param \GuzzleHttp\Client $guzzle
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLogin
     */
    public function setGuzzle(GuzzleClient $guzzle): self
    {
        $this->guzzle = $guzzle;

        return $this;
    }

    /**
     * Set the openid.realm either by passing the URL or the domain only.
     *
     * @param string $realm
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLogin
     */
    public function setRealm(?string $realm = null): self
    {
        $host = str_replace(['https://', 'http://'], '', $realm);

        if (empty($host) || filter_var($host, FILTER_VALIDATE_DOMAIN) === false) {
            $host = $this->request->getHttpHost();
        }

        $realm = (self::$isHttps ? 'https' : 'http').'://'.$host;

        if (!filter_var($realm, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('$realm: `'.$realm.'` is not a valid URL.');
        }

        $this->realm = $realm;

        return $this;
    }

    /**
     * @return string
     */
    public function getRealm(): string
    {
        if (empty($this->realm)) {
            $this->setRealm();
        }

        return $this->realm;
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
            if ($this->request->has('openid_error')) {
                throw new Exception('OpenID Error: '.$this->request->input('openid_error'));
            }

            return false;
        }

        $steamid = $this->validate();

        if ($validated = !empty($steamid)) {
            $this->steamUser = new SteamUser($steamid);
        }

        return $validated;
    }

    /**
     * Return the steamid if validated.
     *
     * @return string|null
     */
    public function validate(): ?string
    {
        if (!$this->request->has('openid_signed') || $this->request->query('openid_claimed_id') !== $this->request->query('openid_identity')) {
            return null;
        }

        $params = [
            'openid.sig'    => $this->request->query('openid_sig'),
            'openid.ns'     => self::OPENID_SPECS,
            'openid.mode'   => 'check_authentication',
            'openid.signed' => $this->request->query('openid_signed'),
        ];

        foreach (explode(',', $params['openid.signed']) as $param) {
            if ($param === 'signed') {
                continue;
            }

            $params['openid.'.$param] = $this->request->query('openid_'.$param);
        }

        $this->response = $this->guzzle->post($this->request->query('openid_op_endpoint'), [
            'timeout'     => config('steam-login.timeout'),
            'form_params' => $params,
        ]);

        $this->openIdResponse = $result = $this->response->getBody()->getContents();

        return preg_match('#is_valid\s*:\s*true#i', $result) === 1 && preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $this->request->query('openid_claimed_id'), $matches) === 1 ? (is_numeric($matches[1]) ? $matches[1] : null) : null;
    }

    /**
     * Redirect the user to steam's login page.
     *
     * @return RedirectResponse
     */
    public function redirectToSteam(): RedirectResponse
    {
        return redirect($this->getLoginUrl());
    }

    /**
     * Return the user to the page they were on before logging in
     * or home if no valid ?redirect given.
     *
     * @return RedirectResponse
     */
    public function previousPage(): RedirectResponse
    {
        return redirect($this->getRedirectTo());
    }

    /**
     * Return the steam login url.
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        if (empty($this->loginUrl)) {
            $this->loginUrl = $this->buildLoginUrl();
        }

        return $this->loginUrl;
    }

    /**
     * Return player object and optionally choose to retrieve profile info.
     *
     * @param bool $info
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamUser
     */
    public function getSteamUser(bool $info = false): SteamUser
    {
        return $info ? $this->steamUser->getUserInfo() : $this->steamUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlayer(bool $info = false): SteamUser
    {
        return $this->getSteamUser($info);
    }

    /**
     * Return Guzzle response of POSTing to Steam's OpenID.
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Return the OpenID response.
     *
     * @return string
     */
    public function getOpenIdResponse(): string
    {
        return $this->openIdResponse;
    }

    /**
     * Check if HTTPS.
     *
     * @return bool
     */
    public function isHttps(): bool
    {
        return self::$isHttps;
    }

    /**
     * Check if query parameters are valid post steam login.
     *
     * @param \Illuminate\Http\Request|null $request
     *
     * @return bool
     */
    public function validRequest(?Request $request = null): bool
    {
        $params = [
            'openid_assoc_handle',
            'openid_claimed_id',
            'openid_sig',
            'openid_signed',
        ];

        return $request ? $request->filled($params) : $this->request->filled($params);
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
        return sprintf('<a href="%s" class="laravel-steam-login-button"><img src="%s" alt="Sign In Through Steam" /></a>', $this->getLoginUrl(), self::button($type));
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
