<?php

namespace App\Support;

use App\Enums\NarrationStyle;

class ScriptPhrases
{
    /**
     * One spoken/caption chunk per narration segment — TTS and on-screen text stay in lockstep.
     *
     * @return array<int, string>
     */
    public static function split(string $text): array
    {
        $profile = NarrationProfile::for(NarrationStyle::fromConfig());
        $segments = NarrationScriptParser::parse($text, $profile);

        return array_values(array_map(
            static fn (NarrationSegment $segment) => $segment->text,
            array_filter($segments, static fn (NarrationSegment $segment) => $segment->isSpeech()),
        ));
    }

    /**
     * @param  array<int, string>  $phrases
     * @return array<int, string>
     */
    public static function mergeShortPhrases(array $phrases): array
    {
        if ($phrases === []) {
            return [];
        }

        $merged = [];
        $index = 0;

        while ($index < count($phrases)) {
            $chunk = $phrases[$index];

            while (
                $index + 1 < count($phrases)
                && self::shouldMergeWithNext($chunk, $phrases[$index + 1])
            ) {
                $index++;
                $chunk = trim($chunk.' '.$phrases[$index]);
            }

            $merged[] = $chunk;
            $index++;
        }

        return $merged;
    }

    public static function isShortPhrase(string $phrase): bool
    {
        return mb_strlen(trim($phrase)) <= 22 || str_word_count($phrase) <= 3;
    }

    private static function shouldMergeWithNext(string $current, string $next): bool
    {
        if (KlausScriptBookends::matchesOutro($next)) {
            return false;
        }

        if (mb_strlen($current) > 45 || str_word_count($current) > 5) {
            return false;
        }

        return self::isShortPhrase($current);
    }
}
