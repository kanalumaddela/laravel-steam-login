# Steam Auth/Login for Laravel 5.5+


This isn't gay like the other laravel steam auths.

## Setup

---

```
composer require kanalumaddela/laravel-steam-login
composer install
```

#### Config Files
```
php artisan vendor:publish
```
`config/steam-login.php` - set here or in your `.env`
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

---

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

            // if user doesn't exist, create them
            if (is_null($user)) {
                $user = User::create([
                            'name' => $player->name,
                            'steamid' => $player->steamid,
                            'registered_ip' => $request->ip(),
                        ]);
            } else { // user already exists, update these columns
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