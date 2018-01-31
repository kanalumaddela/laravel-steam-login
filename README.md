# Steam Auth/Login for Laravel 5.5+

[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

This isn't gay like the other laravel steam auths.

## Setup

```
composer require kanalumaddela/laravel-steam-login
composer install
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
	 * Return route
	 */
	'return_route' => env('STEAM_RETURN', '/auth/steam'),

	/**
	 * Timeout
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
Route::get('login', 'Auth\SteamLoginController@login')->name('login');
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
     * SteamLoginControler constructor
     *
     * @param SteamLogin $steam
     */
    public function __construct(SteamLogin $steam)
    {
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
    public function handle(Request $request)
    {
        if ($this->steam->validate()) {

            // get player's info
            $player = $this->steam->player;

            // get user from DB
            $user = User::where('steamid', $player->steamid)->first();

            // create user if they don't exist, update columns if they do, you choose how you want to do this
            if (is_null($user)) {
                $user = User::create([
                            'name' => $player->name,
                            'steamid' => $player->steamid,
                            'registered_ip' => $request->ip(),
                        ]);
            } else {
                $user->update([
                    'last_activity' => Carbon::now()
                ]);
            }

            Auth::login($user, true); // login and remember user

            return $this->steam->return(); // redurect user back to the page they were on
        }

        return redirect('/'); // now isn't this better than redirecting the user BACK to steam *cough*
    }
}
```

## Docs


**Bolded** - XML method only  
*Italicized* - API method only

| var                      | description           | example |
| :-------                 | :--------------       | ---: |
| $player->steamid         | 64 bit steamid        | 76561198152390718 |
| $player->name            | name                  | kanalumaddela |
| $player->realName        | real name             | Sam |
| $player->playerState     | status                | Online/Offline |
| $player->stateMessage    | status message        | Online/Offline **Last Online/In Game <game>** *Busy/Away/Snooze/Looking to <trade/play>* |
| $player->privacyState    | profile privacy       | Private **Friendsonly** |
| $player->visibilityState | visibility state      | <1/2/3> |
| $player->avatarSmall     | small avatar          | image from (non-https)**cdn.akamai.steamstatic.com** / (https)*steamcdn-a.akamaihd.net*|
| $player->avatarMedium    | medium avatar         | ^ |
| $player->avatarLarge     | large avatar          | ^ |
| $player->joined          | date of joining steam | January 1st, 2018 (format is consistent with XML method) |