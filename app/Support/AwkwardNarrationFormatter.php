<?php

namespace App\Support;

class AwkwardNarrationFormatter
{
    /**
     * Restructure a script into short, deadpan observations — one idea per line.
     */
    public static function format(string $script): string
    {
        $script = trim($script);

        if ($script === '') {
            return '';
        }

        $lines = [];

        foreach (self::splitSentences($script) as $sentence) {
            foreach (self::breakIntoObservations($sentence) as $observation) {
                $lines[] = $observation;
            }
        }

        return implode("\n\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    private static function splitSentences(string $text): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        $sentences = preg_split('/(?<=[.!?])\s+/u', $normalized) ?: [$normalized];

        return array_values(array_filter(
            array_map(static fn (string $sentence) => trim($sentence), $sentences),
            static fn (string $sentence) => $sentence !== '',
        ));
    }

    /**
     * @return array<int, string>
     */
    private static function breakIntoObservations(string $sentence): array
    {
        $sentence = trim($sentence);

        if ($sentence === '') {
            return [];
        }

        if (mb_strlen($sentence) <= 55 && str_word_count($sentence) <= 10) {
            return [self::ensureTerminalPunctuation($sentence)];
        }

        $parts = preg_split('/,\s+(?=(?:and|but|then|so|who|which|when|because|after|before)\s)/iu', $sentence) ?: [$sentence];

        if (count($parts) === 1) {
            $parts = preg_split('/\s+(?:and|but|then)\s+/iu', $sentence, 2) ?: [$sentence];
        }

        $observations = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (mb_strlen($part) > 55) {
                $subParts = preg_split('/(?<=[.!?])\s+/u', $part) ?: [$part];

                foreach ($subParts as $subPart) {
                    $subPart = trim($subPart);

                    if ($subPart !== '') {
                        $observations[] = self::ensureTerminalPunctuation($subPart);
                    }
                }

                continue;
            }

            $observations[] = self::ensureTerminalPunctuation($part);
        }

        return $observations !== [] ? $observations : [self::ensureTerminalPunctuation($sentence)];
    }

    private static function ensureTerminalPunctuation(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (! preg_match('/[.!?…]$/u', $text)) {
            $text .= '.';
        }

        return $text;
    }
}
