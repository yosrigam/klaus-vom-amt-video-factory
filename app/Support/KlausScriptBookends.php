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
        $body = self::stripBookends(trim($script));

        if ($body !== '' && ! preg_match('/[.!?…]$/u', $body)) {
            $body .= '.';
        }

        return collect([self::intro(), $body, ...self::outroLines()])
            ->filter(static fn (string $part) => $part !== '')
            ->implode("\n\n");
    }

    public static function stripBookends(string $script): string
    {
        $body = trim($script);

        $body = preg_replace('/^Klaus vom Amt hier\.?\s*/iu', '', $body) ?? $body;

        foreach (self::allOutroVariants() as $outro) {
            $pattern = '/\s*'.preg_quote($outro, '/').'\s*$/iu';
            $body = preg_replace($pattern, '', $body) ?? $body;
        }

        return trim($body);
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
1. Start with this exact German intro on its own line:
   Klaus vom Amt hier.

2. Body in English — awkwardflyer pacing:
   - short factual observations
   - understated reactions
   - dry punchlines
   - blank line between beats (one idea per line)
   - avoid overexplaining the joke
   - dry, awkward, bureaucratic humor
   - no cheerful sign-off, no CTA, no "thanks for watching", no "follow for more"

3. End with these exact German outro lines (each on its own line, in this order):
   Der Vorgang ist abgeschlossen.
   Auf Wiedersehen.
   (Case-file closed, then a dry bureaucratic farewell — not a cheerful CTA.)
TEXT;
    }
}
