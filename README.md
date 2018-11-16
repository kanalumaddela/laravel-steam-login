# Steam Auth/Login for Laravel 5.5+

[![Maintainability](https://api.codeclimate.com/v1/badges/2c8a9db3372f9c080791/maintainability)](https://codeclimate.com/github/kanalumaddela/laravel-steam-login/maintainability)
[![Packagist](https://img.shields.io/packagist/dt/kanalumaddela/laravel-steam-login.svg?style=flat-square&maxAge=3600)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![Packagist version](https://img.shields.io/packagist/v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

Make sure you have made/performed your migrations along with updating your `User` model if you plan to follow the examples. I suggest doing whatever works best for you, but certain suggestions should be followed.

## 1.x
- [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/1.x)
- PHP 7.0+
- Laravel 5.5+

## 2.x
- [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/2.x)
- PHP 7.1+
- Laravel 5.6+

### Quick Setup (2.x)
1. Install via composer
```
composer require kanalumaddela/laravel-steam-login
```
2. Publish config files
```
php artisan vendor:publish --force --provider kanalumaddela\LaravelSteamLogin\SteamLoginServiceProvider
```
3. Edit `routes/web.php`
```php
Route::get('login/steam', 'Auth\SteamLoginController@login')->name('login.steam');
Route::get('auth/steam', 'Auth\SteamLoginController@auth')->name('auth.steam');

Route::post('logout', 'Auth\LoginController@logout')->name('logout'); // or Auth::routes(); if you're using built in auth also
```
4. Make and setup controller
```
php artisan make:controller Auth/SteamLoginController
```
```php
<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use kanalumaddela\LaravelSteamLogin\Http\Controllers\SteamLoginHandlerController;
use kanalumaddela\LaravelSteamLogin\SteamUser;

class SteamLoginController extends SteamLoginHandlerController
{
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        // find user by steam account id
        $user = User::where('steam_account_id', $steamUser->accountId)->first();

        // if the user doesn't exist, create them
        if (!$user) {
            $user = User::create([
                'name' => $steamUser->name,
                'steam_account_id' => $steamUser->accountId,
            ]);
        }

        // I suggest NOT passing the $remember arg and properly setting up a remember token system.
        // Either be lazy and use $remember or be even lazier and make the session length very long.
        Auth::login($user);
    }
    
    /**
     * Throw SteamLogin exception.
     *
     * @param Exception $exception
     *
     * @throws Exception
     */
    public function error(Exception $exception)
    {
        // failed to login
        throw $exception;
    }
}
```

## Credits

Thanks to these libs which led me to make this
- https://github.com/Ehesp/Steam-Login (Parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (Getting me to create a laravel steam auth that isn't bad, couldn't bother giving credit to Ehesp after *stealing* his code)
