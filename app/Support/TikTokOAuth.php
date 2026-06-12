<?php

namespace App\Support;

class TikTokOAuth
{
    public static function redirectUri(): string
    {
        $configured = config('services.tiktok.redirect_uri');

        if ($configured) {
            return $configured;
        }

        $appUrl = rtrim(config('app.url'), '/');

        return str_replace('http://', 'https://', $appUrl).'/tiktok/callback';
    }
}
