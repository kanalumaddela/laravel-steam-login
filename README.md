# Steam Login/Auth for Laravel/Lumen 5.5+ / 6.x+ / 7.x+ / 8.x+

[![Maintainability](https://api.codeclimate.com/v1/badges/2c8a9db3372f9c080791/maintainability)](https://codeclimate.com/github/kanalumaddela/laravel-steam-login/maintainability)
[![Packagist](https://img.shields.io/packagist/dt/kanalumaddela/laravel-steam-login.svg?style=flat-square&maxAge=3600)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![Packagist version](https://img.shields.io/packagist/v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

Make sure you have made/performed the appropriate migrations. I suggest doing whatever works best for you, but certain
suggestions should be followed to avoid trouble.

| Version | PHP Version | Laravel/Lumen Version         | Docs |
| ------- | ----------- | ----------------------------- | ---- |
| 1.x     | 7.0+        | 5.5+                          | [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/1.x) |
| 2.x     | 7.1+        | 5.6+                          | [Docs](https://github.com/kanalumaddela/laravel-steam-login/wiki/2.x) |
| 3.x     | 7.2+        | 6.0+ / 7.0+ / 8.0+            | [Docs (I/P)](https://github.com/kanalumaddela/laravel-steam-login/wiki/3.x) |

## Features

- Laravel/Lumen supported
- Optionally redirect users to the previous page before logging in
- Included abstract controller and routes for easy setup
- `SteamUser`class to easily retrieve a player's data

## [3.x / 2.x] Quick Setup

1. Install library

```
composer require kanalumaddela/laravel-steam-login
```

2. Publish files

```
php artisan vendor:publish --force --provider kanalumaddela\LaravelSteamLogin\SteamLoginServiceProvider
```

3. Create Controller

```
php artisan make:controller Auth\SteamLoginController
```

4. Add routes `routes/web.php`

```php
use App\Http\Controllers\Auth\SteamLoginController;
use kanalumaddela\LaravelSteamLogin\Facades\SteamLogin;

//...

// If using steam login only, add ['include_login_route' => true]
// to also add a /login route,

SteamLogin::routes([
    'controller' => SteamLoginController::class,
]);
```

4. Edit Controller `App\Http\Controllers\Auth\SteamLoginController.php`
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\Http\Controllers\AbstractSteamLoginController;
use kanalumaddela\LaravelSteamLogin\SteamUser;

class SteamLoginController extends AbstractSteamLoginController
{
    /**
     * {@inheritdoc}
     */
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        // auth logic goes here, below assumes User model with `steam_account_id` attribute 
        // $user = User::where('steam_account_id', $steamUser->accountId)->first();
        // \Illuminate\Support\Facades\Auth::login($user);
    }
}
```

---

## Credits

Thanks to these libs which led me to make this

- https://github.com/Ehesp/Steam-Login (Parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (Getting me to create a laravel steam auth/login that isn't bad)
