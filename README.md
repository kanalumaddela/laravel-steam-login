# Steam Auth/Login for Laravel 5.5+

[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

This isn't gay like the other laravel steam auths.

## Setup

in your composer.json add
```
"kanalumaddela/laravel-steam-login": "~1.0"
```
then `composer install` OR
```
composer require kanalumaddela/laravel-steam-login
```

#### Config
```
php artisan vendor:publish
```
Select `kanalumaddela\LaravelSteamLogin\SteamLoginServiceProvider` as the provider's files you want to publish

`config/steam-login.php` - set in `.env` or save in the config
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

`routes/web.php`
```php
Route::get('login', 'Auth\SteamLoginController@login')->name('login.steam');
Route::get('logout', 'Auth\LoginController@logout')->name('logout'); // laravel's default logout

Route::get('auth/steam', 'Auth\SteamLoginController@handle')->name('auth.steam');
```

## Usage

```
 php artisan make:controller Auth/SteamLoginController
```
Open it up and paste this
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
     * SteamLoginControler constructor
     *
     * @param SteamLogin $steam
     */
    public function __construct(SteamLogin $steam, Request $request)
    {
        $this->steam = $steam;
        $this->request = $request;
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
        if ($this->steam->validate()) {

            // get player's info
            $player = $this->steam->player;

            // get user from DB
            $user = $this->findOrNewUser($player);

            // login and remember user
            Auth::login($user, true);
        }

        /*
            now isn't this better than redirecting the user BACK to steam? *cough*
            you can choose to redirect to steam if you want i guess... return $this->login()
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

        // if user exists, update something, your choice to do something like this
        if (!is_null($user)) {
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

## Player Info

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
| $player->joined          | date of joining steam | January 1st, 2018 (to be consisten with XML method) |

## Credits

Thanks to these libs which led me to make this
- https://github.com/Ehesp/Steam-Login (parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (getting me to create a laravel steam auth that isn't shit, your code *totally* doesn't look like Ehesp's you cuck, for others reading this compare the code, invisnik can't even give proper credit)