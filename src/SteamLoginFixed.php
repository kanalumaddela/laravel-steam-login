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

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use kanalumaddela\LaravelSteamLogin\Interfaces\SteamLoginInterface;
use const PHP_URL_HOST;
use const PHP_URL_PORT;
use function config;
use function get_class;
use function in_array;
use function parse_url;
use function redirect;
use function route;
use function sprintf;
use function strpos;
use function url;

class SteamLoginFixed implements SteamLoginInterface
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
        $this->app = $app;
        $this->request = $app->get('request');
        self::$isLaravel = strpos(get_class($app), 'Lumen') === false;
        self::$isHttps = $this->request->server('HTTPS', 'off') !== 'off' || $this->request->server('SERVER_PORT') == 443 || $this->request->server('HTTP_X_FORWARDED_PROTO') === 'https';

        $originalScheme = $this->request->getScheme();
        if (self::$isHttps && !$this->request->isSecure() && $originalScheme !== 'https') {
            $this->app->get('url')->forceScheme('https');
        }

        $this->realm = (self::$isHttps ? 'https' : 'http').'://'.$this->request->getHttpHost();

        try {
            $this->loginRoute = route(config('steam-login.routes.login'));
        } catch (InvalidArgumentException $e) {
            $this->loginRoute = url(config('steam-login.routes.login'));
        }

        try {
            $this->authRoute = route(config('steam-login.routes.auth'));
        } catch (InvalidArgumentException $e) {
            $this->authRoute = url(config('steam-login.routes.auth'));
        }

        $this->authRoute = route(config('steam-login.routes.auth'));

        $this->loginUrl = $this->buildLoginUrl($this->authRoute);

        $this->app->get('url')->forceScheme($originalScheme);
    }

    /**
     * Build the login url with.
     *
     * @param string|null $return
     * @param string|null $redirectTo
     *
     * @return string
     */
    public function buildLoginUrl(string $return = null, string $redirectTo = null): string
    {
        if (empty($return)) {
            $this->authRoute = $return = route(config('steam-login.routes.auth'));
        }
        $this->setRedirectTo($redirectTo);

        $params = self::$openIdParams;

        if (parse_url($this->realm, PHP_URL_PORT) !== parse_url($return, PHP_URL_HOST)) {
            throw new InvalidArgumentException(sprintf('realm: %s and return_to: %s do not have matching hosts', $this->realm, $return));
        }

        $params['openid.realm'] = $this->realm;
        $params['openid.return_to'] = $return.(self::$isLaravel ? '?redirect='.$this->redirectTo : '');

        return self::OPENID_STEAM.'?'.http_build_query($params);
    }

    /**
     * @param string $redirectTo
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLoginFixed
     */
    public function setRedirectTo(string $redirectTo = null): self
    {
        if (empty($redirectTo) || in_array($redirectTo, [$this->loginRoute, $this->authRoute])) {
            $redirectTo = url('/');
        }

        $this->redirectTo = $redirectTo;

        return $this;
    }

    public function setGuzzle(GuzzleClient $guzzle): self
    {
        $this->guzzle = $guzzle;

        return $this;
    }

    /**
     * @param string $realm
     *
     * @return \kanalumaddela\LaravelSteamLogin\SteamLoginFixed
     */
    public function setRealm(string $realm): self
    {
        $this->realm = $realm;

        return $this;
    }

    /**
     * Return the steamid if validated.
     *
     * @return string|null
     */
    public function validate(): ?string
    {
        if ($this->request->query('openid_claimed_id') !== $this->request->query('openid_identity')) {
            return null;
        }

        $params = [
            //'openid.assoc_handle' => $this->request->query('openid_assoc_handle'),
            'openid.signed' => $this->request->query('openid_signed'),
            'openid.sig'    => $this->request->query('openid_sig'),
            'openid.ns'     => self::OPENID_SPECS,
        ];

        foreach (explode(',', $params['openid.signed']) as $param) {
            $params['openid.'.$param] = $this->request->query('openid_'.$param);
        }

        $params['openid.mode'] = 'check_authentication';

        $this->response = $this->guzzle->post(self::OPENID_STEAM, [
            'connect_timeout' => config('steam-login.timeout'),
            'form_params'     => $params,
        ]);

        $this->openIdResponse = $result = $this->response->getBody();

        return preg_match("#is_valid\s*:\s*true#i", $result) === 1 && preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $this->request->query('openid_claimed_id'), $matches) === 1 ? (is_numeric($matches[1]) ? $matches[1] : null) : null;
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
