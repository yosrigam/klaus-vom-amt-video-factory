<?php

namespace App\Enums;

enum NarrationStyle: string
{
    case Default = 'default';
    case Awkward = 'awkward';

    public static function fromConfig(): self
    {
        $value = config('klaus.narration_style', self::Default->value);

        return self::tryFrom((string) $value) ?? self::Default;
    }
}
