<?php

namespace App\Support;

class BackgroundMusic
{
    /**
     * @return array<int, string>
     */
    public static function paths(): array
    {
        $paths = config('klaus.background_music_paths', []);

        if (! is_array($paths)) {
            $paths = [];
        }

        $readable = array_values(array_filter(
            $paths,
            static fn (mixed $path) => is_string($path) && $path !== '' && is_readable($path),
        ));

        if ($readable !== []) {
            return $readable;
        }

        $legacy = config('klaus.background_music_path');

        if (is_string($legacy) && $legacy !== '' && is_readable($legacy)) {
            return [$legacy];
        }

        return [];
    }

    public static function pick(): ?string
    {
        $paths = self::paths();

        if ($paths === []) {
            return null;
        }

        return $paths[array_rand($paths)];
    }

    public static function resolve(?string $preferred = null): ?string
    {
        if (is_string($preferred) && $preferred !== '' && is_readable($preferred)) {
            return $preferred;
        }

        return self::pick();
    }
}
