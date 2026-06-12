<?php

namespace App\Support;

class KlausImagePrompt
{
    private const LOCK_MARKER = 'CHARACTER BIBLE — KLAUS VOM AMT';

    public static function styleLock(): string
    {
        return trim((string) config('klaus.image_style_lock', ''));
    }

    public static function buildFull(string $scenePrompt): string
    {
        $scene = trim($scenePrompt);
        $lock = self::styleLock();

        if ($lock === '') {
            return $scene;
        }

        if ($scene === '') {
            return $lock;
        }

        if (self::containsLock($scene)) {
            return $scene;
        }

        return $lock."\n\n---\n\nSCENE:\n".$scene;
    }

    public static function buildForApi(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            return self::styleLock();
        }

        if (self::containsLock($prompt)) {
            return $prompt;
        }

        return self::buildFull($prompt);
    }

    public static function containsLock(string $prompt): bool
    {
        return str_contains($prompt, self::LOCK_MARKER);
    }
}
