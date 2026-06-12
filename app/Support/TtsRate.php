<?php

namespace App\Support;

final class TtsRate
{
    public static function parse(string $rate): int
    {
        if (preg_match('/^([+-]?\d+)%$/', trim($rate), $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    public static function format(int $percent): string
    {
        return ($percent >= 0 ? '+' : '').$percent.'%';
    }

    public static function withGlobalOffset(string $rate): string
    {
        $offset = (string) config('klaus.edge_tts_rate', '+0%');
        $combined = self::parse($rate) + self::parse($offset);

        return self::format(max(-50, min(50, $combined)));
    }
}
