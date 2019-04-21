<?php
/**
 * Laravel Steam Login
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/laravel-steam-login
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

use Illuminate\Support\Facades\Route;
use kanalumaddela\LaravelSteamLogin\Http\Controllers\SteamLoginController;

Route::get('login/steam', [SteamLoginController::class, 'login;'])->name('login.steam');
Route::get('auth/steam', [SteamLoginController::class, 'authenticate;'])->name('auth.steam');
