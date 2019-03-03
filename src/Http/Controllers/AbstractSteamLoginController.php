<?php

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\Interfaces\SteamControllerInterface;
use kanalumaddela\LaravelSteamLogin\SteamLogin;

abstract class AbstractSteamLoginController extends Controller implements SteamControllerInterface
{
    /**
     * SteamLogin instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * SteamLogin instance.
     *
     * @var SteamLogin
     */
    protected $steam;

    /**
     * AbstractSteamLoginController constructor.
     *
     * @param \Illuminate\Http\Request                    $request
     * @param \kanalumaddela\LaravelSteamLogin\SteamLogin $steam
     */
    public function __construct(Request $request, SteamLogin $steam)
    {
        $this->request = $request;
        $this->steam = $steam;
    }

    /**
     * Redirect to steam login page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(): RedirectResponse
    {
        return $this->redirectToSteam();
    }

    /**
     * Authenticate the current request after returning from
     * steam login page.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function authenticate()
    {
        if ($this->steam->validated()) {
            $result = $this->authenticated($this->request, $this->steam->getPlayer());

            if (!empty($result)) {
                return  $result;
            }
        } else {
            throw new Exception('Steam Login failed. Response: '.$this->steam->getOpenIdResponse());
        }

        return $this->steam->previousPage();
    }

}