<?php
/**
 * Laravel Steam Login.
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace kanalumaddela\LaravelSteamLogin\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use kanalumaddela\LaravelSteamLogin\Interfaces\SteamLoginControllerInterface;
use kanalumaddela\LaravelSteamLogin\SteamLogin;

abstract class AbstractSteamLoginController extends Controller implements SteamLoginControllerInterface
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
     * {@inheritdoc}
     */
    public function redirectToSteam(): RedirectResponse
    {
        return $this->steam->redirectToSteam();
    }

    /**
     * Keep for deprecation purposes.
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
     */
    public function authenticate()
    {
        if ($this->steam->validated()) {
            $result = $this->authenticated($this->request, $this->steam->getPlayer());

            if (!empty($result)) {
                return $result;
            }
        } else {
            throw new Exception('Steam Login failed. Response: '.$this->steam->getOpenIdResponse());
        }

        return $this->steam->previousPage();
    }
}
