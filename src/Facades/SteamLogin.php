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

namespace kanalumaddela\LaravelSteamLogin\Facades;

use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;

use function array_replace_recursive;

/**
 * Class SteamLogin.
 *
 * @see     \kanalumaddela\LaravelSteamLogin\SteamLogin
 */
class SteamLogin extends Facade
{
    public static function routes(array $options = [])
    {
        $defaults = [
            'login'               => 'login',
            'auth'                => 'authenticate',
            'include_login_route' => false,
        ];

        $options = array_replace_recursive($defaults, $options);

        if (!isset($options['controller'])) {
            throw new InvalidArgumentException('$options[\'controller\'] not defined');
        }

        /**
         * @var \Illuminate\Routing\Router $router
         */
        $router = static::$app->make('router');

        $router->get('login/steam', [$options['controller'], $options['login']])->name('login.steam');
        $router->get('auth/steam', [$options['controller'], $options['auth']])->name('auth.steam');

        if ($options['include_login_route']) {
            $router->get('login')->name('login');
        }
    }

    protected static function getFacadeAccessor()
    {
        return \kanalumaddela\LaravelSteamLogin\SteamLogin::class;
    }
}
