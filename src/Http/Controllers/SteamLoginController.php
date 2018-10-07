<?php

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamLogin;
use kanalumaddela\LaravelSteamLogin\SteamUser;

class SteamLoginController extends Controller
{
    /**
     * SteamLogin instance.
     *
     * @var SteamLogin
     */
    protected $request;

    /**
     * SteamLogin instance.
     *
     * @var SteamLogin
     */
    protected $steam;

    public function __construct(Request $request, SteamLogin $steam)
    {
        $this->request = $request;
        $this->steam = $steam;
    }

    /**
     * Redirect user to steam login page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login()
    {
        return $this->steam->redirectToSteam();
    }

    /**
     * Validate after returning from steam.
     *
     * @throws Exception
     */
    public function auth()
    {
        try {
            if ($this->steam->validated()) {
                $this->authenticated($this->request, $this->steam->getPlayer());
            }
        } catch (Exception $e) {
            $this->error($e);
        }

        return $this->steam->previousPage();
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
        throw $exception;
    }

    /**
     * Called after the user is successfully validated.
     *
     * @param Request   $request
     * @param SteamUser $steamUser
     */
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        // override this thx
    }
}
