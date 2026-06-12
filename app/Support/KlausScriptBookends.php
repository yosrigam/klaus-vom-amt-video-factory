<?php

namespace App\Support;

class KlausScriptBookends
{
    /** @var array<int, string> */
    private const LEGACY_OUTROS = [
        'Auf Wiedersehen.',
        'Goodbye.',
        'Vorgang abgeschlossen.',
    ];

    /** English sign-offs the model sometimes adds; stripped before German bookends are applied. */
    private const ENGLISH_OUTRO_VARIANTS = [
        'The process is complete.',
        'Process complete.',
        'The procedure is complete.',
        'Procedure complete.',
        'Case closed.',
    ];

    public static function intro(): string
    {
        return trim((string) config('klaus.script_german_intro', 'Klaus vom Amt hier.'));
    }

    public static function outro(): string
    {
        return trim((string) config(
            'klaus.script_german_outro',
            "Der Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.",
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function outroLines(): array
    {
        $outro = self::outro();

        if ($outro === '') {
            return [];
        }

        $lines = preg_split('/\n\s*\n/', $outro) ?: [$outro];

        return array_values(array_filter(
            array_map(static fn (string $line) => trim($line), $lines),
            static fn (string $line) => $line !== '',
        ));
    }

    public static function apply(string $script): string
    {
        $body = self::sanitizeBody(trim($script));

        if ($body !== '' && ! preg_match('/[.!?…]$/u', $body)) {
            $body .= '.';
        }

        return collect([self::intro(), $body, ...self::outroLines()])
            ->filter(static fn (string $part) => $part !== '')
            ->implode("\n\n");
    }

    public static function sanitizeBody(string $script): string
    {
        return self::stripEnglishOutroLines(self::stripBookends($script));
    }

    public static function stripBookends(string $script): string
    {
        $body = trim($script);

        $body = preg_replace('/^Klaus vom Amt hier\.?\s*/iu', '', $body) ?? $body;
        $body = preg_replace('/^Klaus vom Amt here\.?\s*/iu', '', $body) ?? $body;

        $strippableOutros = [...self::allOutroVariants(), ...self::ENGLISH_OUTRO_VARIANTS];

        do {
            $previous = $body;

            foreach ($strippableOutros as $outro) {
                $pattern = '/\s*'.preg_quote($outro, '/').'\s*$/iu';
                $body = preg_replace($pattern, '', $body) ?? $body;
            }

            $body = trim($body);
        } while ($body !== $previous);

        return $body;
    }

    public static function stripEnglishOutroLines(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        $normalize = static fn (string $value): string => mb_strtolower(trim($value, " \t\n\r\0\x0B."));

        $englishOutros = array_map($normalize, [...self::ENGLISH_OUTRO_VARIANTS, 'Goodbye.']);

        $blocks = preg_split('/\n\s*\n/', $body) ?: [$body];

        $filtered = array_values(array_filter(
            $blocks,
            static fn (string $block): bool => ! in_array($normalize($block), $englishOutros, true),
        ));

        return trim(implode("\n\n", $filtered));
    }

    public static function matchesOutro(string $phrase): bool
    {
        $normalize = static fn (string $value): string => mb_strtolower(trim($value, " \t\n\r\0\x0B."));

        $normalized = $normalize($phrase);

        foreach (self::allOutroVariants() as $candidate) {
            if ($normalized === $normalize($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function allOutroVariants(): array
    {
        return array_values(array_unique([
            ...self::outroLines(),
            ...self::LEGACY_OUTROS,
        ]));
    }

    public static function scriptFormatInstructions(): string
    {
        return <<<TEXT
Script structure (mandatory — no duplicate intro or outro):
1. Do not write an intro. The system prepends:
   Klaus vom Amt hier.

2. Body in English — awkwardflyer pacing:
   - short factual observations
   - understated reactions
   - dry punchlines
   - blank line between beats (one idea per line)
   - avoid overexplaining the joke
   - dry, awkward, bureaucratic humor
   - no cheerful sign-off, no CTA, no "thanks for watching", no "follow for more"
   - no English closing lines such as "The process is complete" or "Goodbye" — German bookends are injected automatically

3. Do not write an outro. The system appends these German lines after the body:
   Der Vorgang ist abgeschlossen.
   Auf Wiedersehen.
TEXT;
    }
}
