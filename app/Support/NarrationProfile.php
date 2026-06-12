<?php

namespace App\Support;

use App\Enums\NarrationStyle;

final readonly class NarrationProfile
{
    public function __construct(
        public float $sentencePauseSeconds,
        public float $shortPhrasePauseSeconds,
        public float $bookendPauseSeconds,
        public float $lineBreakPauseSeconds,
        public float $dramaticPauseMultiplier,
        public bool $mergeShortPhrases,
        public bool $preserveLineBreaks,
        public bool $applyFormatter,
        public string $rate,
        public string $pitch,
        public string $volume,
    ) {}

    public static function for(NarrationStyle $style): self
    {
        $profile = config("klaus.narration_styles.{$style->value}");

        if (! is_array($profile)) {
            throw new \InvalidArgumentException("Unknown narration style profile: {$style->value}");
        }

        return new self(
            sentencePauseSeconds: (float) ($profile['sentence_pause_seconds'] ?? 0.30),
            shortPhrasePauseSeconds: (float) ($profile['short_phrase_pause_seconds'] ?? 0.18),
            bookendPauseSeconds: (float) ($profile['bookend_pause_seconds'] ?? 0.65),
            lineBreakPauseSeconds: (float) ($profile['line_break_pause_seconds'] ?? 0.45),
            dramaticPauseMultiplier: (float) ($profile['dramatic_pause_multiplier'] ?? 1.0),
            mergeShortPhrases: (bool) ($profile['merge_short_phrases'] ?? false),
            preserveLineBreaks: (bool) ($profile['preserve_line_breaks'] ?? true),
            applyFormatter: (bool) ($profile['apply_formatter'] ?? false),
            rate: (string) ($profile['rate'] ?? '+4%'),
            pitch: (string) ($profile['pitch'] ?? '-3Hz'),
            volume: (string) ($profile['volume'] ?? '+0%'),
        );
    }
}
