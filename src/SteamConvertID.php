<?php

namespace kanalumaddela\LaravelSteamLogin;

class SteamConvertID
{
    /**
     * 64 bit steamid
     */
    public $steamid;

    /**
     * STEAM:ID
     */
    public $steamid2;

    /**
     * SteamID3
     */
    public $steamid3;

    public function __construct($steamid)
    {
        $x = ($steamid >> 56) & 0xFF;
        $y = Config::get('steam-login.universe') ? $steamid & 1 : 0;
        $z = ($steamid >> 1) & 0x7FFFFFF;

        $this->steamid = $steamid;
        $this->steamid2 = "STEAM_$x:$y:$z";
        $this->steamid3 = "[U:1:".($z * 2 +$y)."]";

        return $this;
    }


}