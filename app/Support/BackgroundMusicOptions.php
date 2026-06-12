<?php

namespace App\Support;

class BackgroundMusicOptions
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (BackgroundMusic::paths() as $path) {
            $labels[$path] = self::labelFor($path);
        }

        return $labels;
    }

    public static function label(?string $path): string
    {
        if (! is_string($path) || $path === '') {
            return 'Not set — random at render time';
        }

        return self::labelFor($path);
    }

    private static function labelFor(string $path): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        return str($filename)->replace('-', ' ')->title()->toString();
    }
}
