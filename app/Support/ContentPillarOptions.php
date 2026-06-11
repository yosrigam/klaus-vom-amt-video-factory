<?php

namespace App\Support;

class ContentPillarOptions
{
    public static function labels(): array
    {
        return collect(config('content_pillars', []))
            ->mapWithKeys(fn (array $pillar, string $key) => [$key => $pillar['emoji'].' '.$pillar['name']])
            ->all();
    }
}
