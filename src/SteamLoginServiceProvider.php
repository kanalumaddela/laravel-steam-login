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
use function class_exists;
use function config;
use function config_path;
use function copy;
use function file_exists;
use function mkdir;

class SteamLoginServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // hacky stuff for laravel/lumen
        if (class_exists('\Illuminate\Foundation\Application', false)) {
            $this->publishes([__DIR__.'/../config/steam-login.php' => config_path('steam-login.php')]);
        } else {
            // create config file and folder automatically if not found for lumen
            if (!file_exists($this->app->basePath('config').'/steam-login.php')) {
                if (!file_exists($this->app->basePath('config'))) {
                    mkdir($this->app->basePath('config'));
                }

                copy(__DIR__.'/../config/steam-login.php', $this->app->basePath('config').'/steam-login.php');
            }
        }

        if (config('steam-login.use_routes', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/steam-login.php');
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
