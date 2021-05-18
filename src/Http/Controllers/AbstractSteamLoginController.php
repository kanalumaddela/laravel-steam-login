<?php
/*
 * Laravel Steam Login.
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 Maddela
 * @license   MIT
 */

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use kanalumaddela\LaravelSteamLogin\Contracts\SteamLoginControllerInterface;
use kanalumaddela\LaravelSteamLogin\SteamLogin;

abstract class AbstractSteamLoginController implements SteamLoginControllerInterface
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
     * Redirect to steam login page or maybe show a login page if overridden.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(): RedirectResponse
    {
        return $this->redirectToSteam();
    }

    /**
     * Keep for deprecation purposes.
     *
     * @deprecated
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function auth()
    {
        return $this->authenticate();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function authenticate()
    {
        $valid = $this->steam->validated();

        if ($valid && !empty($result = $this->authenticated($this->request, $this->steam->getPlayer()))) {
            return $result;
        }

        if (!$valid && $this->steam->validRequest()) {
            return $this->authenticationFailed($this->request);
        }

        if (!$this->steam->isLaravel() || $this->steam->isLaravel() && url()->previous() === route(config('steam-login.routes.auth'))) {
            return $this->login();
        }

        return $this->steam->previousPage();
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     */
    public function authenticationFailed(Request $request)
    {
        throw new Exception('Steam Login failed. Response: `'.trim($this->steam->getOpenIdResponse()).'`');
    }

    /**
     * {@inheritdoc}
     */
    public function redirectToSteam(): RedirectResponse
    {
        return $this->steam->redirectToSteam();
    }
}
