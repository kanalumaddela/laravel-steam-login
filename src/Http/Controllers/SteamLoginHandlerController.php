<?php

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\SteamLogin;
use kanalumaddela\LaravelSteamLogin\SteamUser;

class SteamLoginHandlerController extends Controller
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
    public function login(): RedirectResponse
    {
        return $this->steam->redirectToSteam();
    }

    /**
     * Validate after returning from steam and redirect to the previous page.
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function auth(): RedirectResponse
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
     * User has been successfully authenticated/validated.
     *
     * @param Request   $request
     * @param SteamUser $steamUser
     */
    public function authenticated(Request $request, SteamUser $steamUser)
    {
        //
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
}
