# Steam Auth/Login for Laravel 5.5+

[![Packagist](https://img.shields.io/packagist/dt/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![Packagist version](https://img.shields.io/packagist/v/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://packagist.org/packages/kanalumaddela/laravel-steam-login)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/kanalumaddela/laravel-steam-login.svg?style=flat-square)]()
[![GitHub stars](https://img.shields.io/github/stars/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/network)
[![GitHub issues](https://img.shields.io/github/issues/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/issues)
[![GitHub license](https://img.shields.io/github/license/kanalumaddela/laravel-steam-login.svg?style=flat-square)](https://github.com/kanalumaddela/laravel-steam-login/blob/master/LICENSE)

This isn't gay like the other laravel steam auths. The example assumes you are using Laravel's default `User` model along with the migration for its table. If you want otherwise, read Laravel docs.

## Requirements

- PHP 7 +
- User tables setup for steam auth (e.g. `steamid` column or a separate table referencing the `users` table)
- A basic understanding of Laravel (& PHP obvs)

# Docs
https://github.com/kanalumaddela/laravel-steam-login/wiki/1.x

## Credits
Thanks to these libs which led me to make this
- https://github.com/Ehesp/Steam-Login (Parts of code used and re-purposed for laravel)
- https://github.com/invisnik/laravel-steam-auth (Getting me to create a laravel steam auth that isn't shit, your code *totally* doesn't look like Ehesp's you cuck. For others reading this compare the code, invisnik can't even give proper credit)