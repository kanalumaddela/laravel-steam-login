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

use function config_path;
use function copy;
use function file_exists;
use function get_class;
use Illuminate\Support\ServiceProvider;
use function mkdir;
use function strpos;

class SteamLoginServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (strpos(get_class($this->app), 'Lumen') === false) {
            $this->publishLaravelConfig();
        } else {
            $this->publishLumenConfig();
        }
    }

    protected function publishLaravelConfig()
    {
        $this->publishes([
            __DIR__.'/../config/steam-login.php' => config_path('steam-login.php'),
        ]);
    }

    protected function publishLumenConfig()
    {
        $path = base_path().'/config';

        if (!file_exists($path)) {
            mkdir($path);
        }

        if (!file_exists($path.'/steam-login.php')) {
            copy(__DIR__.'/../config/steam-login.php', $path.'/steam-login.php');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SteamLogin::class, function ($app) {
            return new SteamLogin($app->get('request'), $app->get('url'), $app);
        });
    }
}
