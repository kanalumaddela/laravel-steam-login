<?php

use Illuminate\Support\Facades\Config;

if (Config::get('steam-login.routes.use')) {
    Route::namespace('\kanalumaddela\LaravelSteamLogin\Http\Controllers')->group(function () {
        Route::get(Config::get('steam-login.routes.login.path'), 'SteamLoginController@login')->name(Config::get('steam-login.routes.login.name'));
        Route::get(Config::get('steam-login.routes.auth.path'), 'SteamLoginController@auth')->name(Config::get('steam-login.routes.auth.name'));
    });
}