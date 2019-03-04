<?php

namespace kanalumaddela\LaravelSteamLogin;

use Illuminate\Support\ServiceProvider;

class SteamLoginServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // hacky stuff for laravel/lumen
        if (\class_exists('\Illuminate\Foundation\Application', false)) {
            $this->publishes([__DIR__.'/../config/steam-login.php' => \config_path('steam-login.php')]);
        } else {
            // create config file and folder automatically if not found
            if (!\file_exists($this->app->basePath('config').'/steam-login.php')) {
                if (!\file_exists($this->app->basePath('config').'/')) {
                    \mkdir($this->app->basePath('config'));
                }

                \copy(__DIR__.'/../config/steam-login.php', $this->app->basePath('config').'/steam-login.php');
            }
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SteamLogin', function($app) {
            return new SteamLogin($app);
        });
    }
}
