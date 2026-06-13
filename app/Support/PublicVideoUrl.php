<?php

namespace App\Support;

final class PublicVideoUrl
{
    public static function forPath(string $videoPath): string
    {
        $base = rtrim((string) (config('klaus.public_video_base_url') ?: config('app.url')), '/');

        return $base.route('klaus.media', ['path' => $videoPath], false);
    }
}
