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

use Illuminate\Support\Facades\Facade;

class SteamLogin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
