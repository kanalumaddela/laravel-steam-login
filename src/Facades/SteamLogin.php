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

namespace kanalumaddela\LaravelSteamLogin\Facades;

use function array_replace_recursive;
use Illuminate\Support\Facades\Facade;
use kanalumaddela\LaravelSteamLogin\Http\Controllers\SteamLoginController;

class SteamLogin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \kanalumaddela\LaravelSteamLogin\SteamLogin::class;
    }

    public static function routes(array $options = [])
    {
        $defaults = [
            'controller' => SteamLoginController::class,
            'login'      => 'login',
            'auth'       => 'authenticate',
        ];

        $options = array_replace_recursive($defaults, $options);

        $router = static::$app->make('router');

        $router->get('login/steam', [$options['controller'], $options['login']])->name('login.steam');
        $router->get('auth/steam', [$options['controller'], $options['auth']])->name('auth.steam');
    }
}
