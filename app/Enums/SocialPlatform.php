<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Youtube = 'youtube';
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';

    public function label(): string
    {
        return match ($this) {
            self::Youtube => 'YouTube',
            self::Instagram => 'Instagram',
            self::Tiktok => 'TikTok',
        };
    }
}
