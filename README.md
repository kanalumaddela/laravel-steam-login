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

| Version | PHP Version | Laravel Version | Docs |
| ------- | ----------- | --------------- | ---- |
| 1.x     | 7.0+        | 5.5+            | [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/1.x) |
| 2.x     | 7.1+        | 5.6+            | [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/2.x) |

## Quick Setup (2.x, Laravel 5.6+)

1. Install library
```
composer require kanalumaddela/laravel-steam-login

php artisan vendor:publish --force --provider kanalumaddela\LaravelSteamLogin\SteamLoginServiceProvider
```

2. Add routes

`routes/web.php`
```php
use App\Http\Controllers\Auth\SteamLoginController;
use kanalumaddela\LaravelSteamLogin\Facades\SteamLogin;

//...

SteamLogin::routes(['controller' => SteamLoginController::class]);
```
```
php artisan make:controller Auth\SteamLoginController
```
`App\Http\Controllers\Auth\SteamLoginController.php`
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use kanalumaddela\LaravelSteamLogin\Http\Controllers\AbstractSteamLoginController;
use kanalumaddela\LaravelSteamLogin\SteamUser;

class SteamLoginController extends AbstractSteamLoginController
{
    /**
     * {@inheritdoc}
     */
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        // auth logic goes here
        // e.g. $user = User::where('steam_account_id', $steamUser->accountId)->first();
    }
}
```

---

## Credits

Thanks to these libs which led me to make this
- https://github.com/Ehesp/Steam-Login (Parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (Getting me to create a laravel steam auth/login that isn't bad)
