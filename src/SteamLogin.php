<?php
/*
 * Laravel Steam Login.
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 Maddela
 * @license   MIT
 */

namespace kanalumaddela\LaravelSteamLogin;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use kanalumaddela\LaravelSteamLogin\Contracts\SteamLoginInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use function config;
use function explode;
use function filter_var;
use function get_class;
use function http_build_query;
use function is_numeric;
use function parse_url;
use function preg_match;
use function redirect;
use function sprintf;
use function strpos;
use function trigger_error;
use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

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
     * The app's original http scheme in case of reverse proxy with ssl e.g. Cloudflare flexible ssl.
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
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $urlGenerator;

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
     * O(1) check for route URLs.
     *
     * @var array
     */
    private $routeChecks = [];

    /**
     * SteamLogin constructor.
     *
     * @param \Illuminate\Http\Request                          $request
     * @param \Illuminate\Contracts\Foundation\Application|null $app
     */
    public function __construct(Request $request, ?Application $app)
    {
        if (PHP_INT_SIZE !== 8) {
            trigger_error('64-bit PHP is required to convert steamids', E_USER_WARNING);
        }

        $this->request = $request;

        $app = empty($app) && function_exists('app') ? app() : $app;

        if (!is_null($app)) {
            $this->urlGenerator = $app->get('url');
        }

        static::$isLaravel = !empty($app) && strpos(get_class($app), 'Lumen') === false;
        static::$isHttps = $this->isHttps();
        static::$originalScheme = $this->request->getScheme();

        if (static::$isHttps && !$this->request->isSecure()) {
            $this->urlGenerator->forceScheme('https');
        }

        unset($request, $urlGenerator, $app);
    }

    /**
     * Check if HTTPS.
     *
     * @return bool
     */
    public function isHttps(): bool
    {
        return
            $this->request->server('HTTPS', 'off') !== 'off' ||
            $this->request->server('SERVER_PORT') == 443 ||
            $this->request->server('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    public function isLaravel(): bool
    {
        return static::$isLaravel;
    }

    public function setLoginRoute(string $route): self
    {
        $this->loginRoute = $this->getRoute($route);

        return $this;
    }

    protected function getRoute(string $route): string
    {
        try {
            $route = $this->urlGenerator->route($route);
        } catch (RouteNotFoundException $exception) {
            $route = $this->urlGenerator->to($route);
        }

        if (filter_var($route, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('$route: '.$route.' is not a valid URL');
        }

        return $route;
    }

    public function setAuthRoute(string $route): SteamLogin
    {
        $this->authRoute = $this->getRoute($route);

        return $this;
    }

    /**
     * Check if login is valid.
     *
     * @throws Exception|\GuzzleHttp\Exception\GuzzleException
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
            $this->steamUser = new SteamUser($steamid, $this->guzzle);
        }

        return $validated;
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
     * Return the steamid if validated.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
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
            'openid.ns'     => static::OPENID_SPECS,
            'openid.mode'   => 'check_authentication',
            'openid.signed' => $this->request->query('openid_signed'),
        ];

        foreach (explode(',', $params['openid.signed']) as $param) {
            if ($param === 'signed') {
                continue;
            }

            $params['openid.'.$param] = $this->request->query('openid_'.$param);
        }

        if (empty($this->guzzle)) {
            $this->setGuzzle(new GuzzleClient());
        }

        $this->response = $this->guzzle->post($this->request->query('openid_op_endpoint'), [
            'timeout'     => config('steam-login.timeout', 5),
            'form_params' => $params,
        ]);

        $this->openIdResponse = $result = $this->response->getBody()->getContents();

        return preg_match('#is_valid\s*:\s*true#i', $result) === 1 && preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $this->request->query('openid_claimed_id'), $matches) === 1 ? (is_numeric($matches[1]) ? $matches[1] : null) : null;
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
     * Redirect the user to steam's login page.
     *
     * @return RedirectResponse
     */
    public function redirectToSteam(): RedirectResponse
    {
        return redirect($this->getLoginUrl());
    }

    /**
     * Return the steam login url.
     *
     * @param bool $rebuildUrl
     *
     * @return string
     */
    public function getLoginUrl(bool $rebuildUrl = false): string
    {
        if (empty($this->loginUrl) || $rebuildUrl) {
            $this->loginUrl = $this->buildLoginUrl();
        }

        return $this->loginUrl;
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
    public function buildLoginUrl(?string $return = null, ?string $redirectTo = null, bool $checkRoutes = true, bool $includeRedirectTo = true): string
    {
        if ($checkRoutes) {
            $this->prepRoutes();
        }

        if (empty($return)) {
            $return = $this->authRoute;
        }

        if ($includeRedirectTo) {
            $this->setRedirectTo($redirectTo);
        }

        $params = static::$openIdParams;
        $this->realm = $this->getRealm();

        if (parse_url($this->realm, PHP_URL_HOST) !== parse_url($return, PHP_URL_HOST)) {
            throw new InvalidArgumentException(sprintf('realm: `%s` and return_to: `%s` do not have matching hosts', $this->realm, $return));
        }

        $params['openid.realm'] = $this->realm;
        $params['openid.return_to'] = $return.(static::$isLaravel && !empty($this->redirectTo) && config('steam-login.enable_redirect_to', true) && $includeRedirectTo ? '?'.http_build_query(['redirect_to' => $this->redirectTo]) : '');

        return static::OPENID_STEAM.'?'.http_build_query($params);
    }

    protected function prepRoutes(): SteamLogin
    {
        $this->routeChecks = [];

        $this->loginRoute = $this->getRoute(config('steam-login.routes.login', 'login.steam'));
        $this->authRoute = $this->getRoute(config('steam-login.routes.auth', 'auth.steam'));

        $this->routeChecks[$this->loginRoute] = true;
        $this->routeChecks[$this->authRoute] = true;

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
     * Set the openid.realm either by passing the URL or the domain only.
     *
     * @param string|null $realm
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLogin
     */
    public function setRealm(?string $realm = null): self
    {
        if (empty($realm)) {
            $host = $this->request->getHttpHost();

            $realm = (static::$isHttps ? 'https' : 'http').'://'.$host;
        } elseif (!filter_var($realm, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('$realm: `'.$realm.'` is not a valid URL.');
        }

        $this->realm = $realm;

        return $this;
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
     * Return the ?redirect_to|redirect URL.
     *
     * @return string
     */
    public function getRedirectTo(): string
    {
        if (!empty($redirect_to = $this->request->get('redirect_to', $this->request->get('redirect')))) {
            $this->setRedirectTo($redirect_to);
        } elseif (empty($this->redirectTo)) {
            $this->setRedirectTo();
        }

        return $this->redirectTo;
    }

    /**
     * @param string|null $redirectTo
     * @param bool        $checkRoutes
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLogin
     */
    public function setRedirectTo(string $redirectTo = null, bool $checkRoutes = true): self
    {
        if (empty($redirectTo)) {
            $redirectTo = $this->isLaravel() ? $this->urlGenerator->previous('/') : $this->urlGenerator->to('/');
        }

        if ($checkRoutes) {
            if (empty($this->routeChecks)) {
                $this->prepRoutes();
            }

            if (isset($this->routeChecks[$redirectTo])) {
                $redirectTo = $this->urlGenerator->to('/');
            } elseif (!filter_var($redirectTo, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('$redirectTo: `'.$redirectTo.'` is not a valid URL');
            }
        }


        $this->redirectTo = $redirectTo;

        return $this;
    }

    /**
     * @param bool $withUserInfo
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamUser
     */
    public function getPlayer(bool $withUserInfo = false): SteamUser
    {
        return $this->getSteamUser($withUserInfo);
    }

    /**
     * Return player object and optionally choose to retrieve profile info.
     *
     * @param bool $withUserInfo
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamUser
     */
    public function getSteamUser(bool $withUserInfo = false): SteamUser
    {
        return $withUserInfo ? $this->steamUser->getUserInfo() : $this->steamUser;
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
    public function getOpenIdResponse(): ?string
    {
        return $this->openIdResponse;
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
        return sprintf('<a href="%s" class="laravel-steam-login-button"><img src="%s" alt="Sign In Through Steam" /></a>', $this->getLoginUrl(), static::buttonImage($type));
    }

    /**
     * Return the URL of Steam Login buttons.
     *
     * @param string $type
     *
     * @return string
     */
    public static function buttonImage(string $type = 'small'): string
    {
        return 'https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_0'.($type === 'small' ? 1 : 2).'.png';
    }
}
