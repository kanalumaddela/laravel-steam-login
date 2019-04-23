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

use Illuminate\Support\ServiceProvider;
use function config;
use function config_path;
use function copy;
use function file_exists;
use function get_class;
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
        $isLaravel = strpos(get_class($this->app), 'Lumen') === false;

        if ($isLaravel) {
            $this->publishLaravelConfig();

            if (config('steam-login.use_all') || config('steam-login.use_routes')) {
                $this->loadRoutesFrom(__DIR__.'/../routes/steam-login.php');
            }
            if (config('steam-login.use_all') || config('steam-login.use_migrations')) {
                $this->loadMigrationsFrom(__DIR__.'/../migrations');
            }
        } else {
            $this->publishLumenConfig();
        }
    }

    protected function publishLaravelConfig()
    {
        $this->publishes([__DIR__.'/../config/steam-login.php' => config_path('steam-login.php')]);
    }

    protected function publishLumenConfig()
    {
        if (!file_exists(config_path('steam-login.php'))) {
            if (!file_exists(config_path())) {
                mkdir(config_path());
            }

            copy(__DIR__.'/../config/steam-login.php', config_path('steam-login.php'));
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SteamLogin', function ($app) {
            return new SteamLogin($app);
        });
    }
}
