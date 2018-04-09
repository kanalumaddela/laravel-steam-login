<?php

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use Illuminate\Support\Facades\Auth;

use kanalumaddela\LaravelSteamLogin\SteamLogin;

class SteamLoginController extends Controller {

    /**
     * Illuminate\Http\Request
     *
     * @var Request
     */
    protected $request;

    /**
     * SteamLogin instance
     *
     * @var SteamLogin
     */
    protected $steamLogin;

    public function __construct(Request $request, SteamLogin $steamLogin)
    {
        $this->request = $request;
        $this->steamLogin = $steamLogin;
    }

    public function login()
    {
        return $this->steamLogin->redirect();
    }

    public function handle()
    {
        if ($this->steamLogin->validRequest()) {
            // get player
            $player = $this->steamLogin->getPlayerInfo(); // or ->getPlayer() to return basic information

            // get user from DB or create them
            $user = $this->findOrNewUser($player);

            // login and remember user
            Auth::login($user, true);
        }

        // return user to the page they were on before logging in
        return $this->steamLogin->returnToOrigin();
    }

    private function findOrNewUser($player)
    {
        $user = User::where('steamid', $player->steamid)->first();

        if (is_null($user)) {
            $user = User::create([
                'name' => $player->name,
                'steamid' => $player->steamid,
                'registered' => Carbon::now()
            ]);
        }

    }


}