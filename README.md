# Steam Auth/Login for Laravel 5.5+

[![Packagist](https://img.shields.io/packagist/dt/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![Packagist version](https://img.shields.io/packagist/v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/kanalumaddela/laravel-steam-login.svg?style=flat-square)]()
[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

This isn't gay like the other laravel steam auths. The example assumes you are using Laravel's default `User` model along with the migration for its table. If you want otherwise, read Laravel docs.

## Requirements

- PHP 7 +
- User tables setup for steam auth (e.g. `steamid` column or a separate table referencing the `users` table)
- A basic understanding of Laravel (& PHP obvs)

## Setup

```
composer require "kanalumaddela/laravel-steam-login"
```

#### Config
```
 php artisan vendor:publish --provider kanalumaddela\LaravelSteamLogin\SteamLoginServiceProvider
```

1. Set your config values in your`.env`, here's what shows in `config/steam-login.php`
   - the xml method allows you to get a player's info without using the steam api, however it is **highly recommended** to use the api method in production

```php
<?php

return [

    /**
     * Login route
     */
    'login_route' => env('STEAM_LOGIN', '/login'),

    /**
     * Return route
     */
    'return_route' => env('STEAM_RETURN', '/auth/steam'),

    /**
     * Timeout when validating
     */
    'timeout' => env('STEAM_TIMEOUT', 15),

    /**
     * Method of retrieving user's info
     */
    'method' => env('STEAM_PROFILE_METHOD', 'xml'),

    /**
     * API key (http://steamcommunity.com/dev/apikey)
     */
    'api_key' => env('STEAM_API_KEY', ''),

];
```
2. Add the routes in `routes/web.php`
```php
// login/logout
Route::get('login/steam', 'Auth\SteamLoginController@login')->name('login.steam'); // incase you want to have other login methods
Route::get('logout', 'Auth\LoginController@logout')->name('logout'); // laravel's default logout, or use the post method if you know prefer

Route::get('auth/steam', 'Auth\SteamLoginController@handle')->name('auth.steam');
```


3. Share the login url and steam buttons (if you choose) across blade templates in `app/Providers/AppServiceProvider.php`
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\View;                                       // <-- add this
use kanalumaddela\LaravelSteamLogin\SteamLogin;                            // <-- add this

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(SteamLogin $steam)                                // boot() --> boot(SteamLogin $steam)
    {
        View::share('steam_login', $steam->loginUrl());
        View::share('steam_button_small', SteamLogin::button('small'));
        View::share('steam_button_large', SteamLogin::button('large'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
```

4. Create the controller

```
 php artisan make:controller Auth/SteamLoginController
```
Paste this
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

use kanalumaddela\LaravelSteamLogin\SteamLogin;

class SteamLoginController extends Controller
{
    /**
     * SteamLogin instance
     *
     * @var SteamLogin
     */
    protected $steam;

    /**
     * Illuminate\Http\Request
     *
     * @var Request $request
     */
    protected $request;


    /**
     * SteamLoginController constructor
     *
     * @param Request $request 
     * @param SteamLogin $steam
     */
    public function __construct(Request $request, SteamLogin $steam)
    {
        $this->request = $request;
        $this->steam = $steam;
    }

    /**
     * Redirect to steam
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function login()
    {
        return $this->steam->redirect();
    }

    /**
     * Validate, get user's info, create/update user, login
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function handle()
    {
        // check if validation succeeded, returns true/false
        if ($this->steam->validate()) {

            // get player's info
            $player = $this->steam->player;

            // get user from DB or create and return them
            $user = $this->findOrNewUser($player);

            // login and remember user
            Auth::login($user, true);
        }

        /*
            now isn't this better than redirecting the user BACK to steam? *cough*
            you can choose to redirect to steam if you want i guess... return $this->login()
            better to session flash ->with() and redirect to a page so the user knows what happened if auth fails
        */
        return $this->steam->return();
    }

    /**
     * Find existing user or insert one
     *
     * @param $player
     * @return mixed
     */
    protected function findOrNewUser($player) {

        // find user in DB
        $user = User::where('steamid', $player->steamid)->first();

        // check if user exists in DB
        if (!is_null($user)) {
            // update and save user
            $user->update([
                'avatar' => $player->avatarLarge
            ]);
        } else {
            // create user and insert into DB
            $user = User::create([
                        'name' => $player->name,
                        'steamid' => $player->steamid,
                        'avatar' => $player->avatarLarge,
                        'registered_ip' => $this->request->ip(),
                    ]);
        }

        return $user;
    }
}
```

## Docs

#### Sign in through Steam buttons  
Can be used in blade templates like  
```html
<a href="{{ $steam_login }}"><img src="{{ $steam_button_small }}" /></a>
```  

`SteamLogin::button($type)` - returns the image url for the sign in through steam button

`small` - ![](https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_01.png)
 
`large` - ![](https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_02.png)

&nbsp;

#### Player Info

**Bolded** - XML method only  
*Italicized* - API method only

| var                      | description           | example |
| :---                     | :---                  | ---: |
| $player->steamid         | 64 bit steamid        | 76561198152390718 |
| $player->steamid2        | 32 bit steamid        | STEAM_0:0:96062495 |
| $player->steamid3        | SteamID3              | [U:1:192124990] |
| $player->name            | name                  | kanalumaddela |
| $player->realName        | real name             | Sam |
| $player->playerState     | status                | Online/Offline |
| $player->stateMessage    | status message        | Online/Offline <br> **Last Online/In Game <game>** <br> *Busy/Away/Snooze/Looking to <trade/play>* |
| $player->privacyState    | profile privacy       | Private <br> **Friendsonly** |
| $player->visibilityState | visibility state      | <1/2/3> |
| $player->avatarSmall     | small avatar          | avatar url <br> **cdn.akamai.steamstatic.com** (http) <br> *steamcdn-a.akamaihd.net* (https) |
| $player->avatarMedium    | medium avatar         | ^ |
| $player->avatarLarge     | large avatar          | ^ |
| $player->joined          | date of joining steam | January 1st, 2018 (format is consistent with XML method) |

## Credits

Thanks to these libs which led me to make this
- https://github.com/Ehesp/Steam-Login (Parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (Getting me to create a laravel steam auth that isn't shit, your code *totally* doesn't look like Ehesp's you cuck. For others reading this compare the code, invisnik can't even give proper credit)