<?php

namespace App\Support;

use App\Enums\KlausDeliveryStyle;

final class KlausDeliveryClassifier
{
    /** @var array<int, string> */
    private const PUNCHLINE_PREFIXES = [
        'unfortunately',
        'naturally',
        'somehow',
        'technically',
        'of course',
        'this did not',
        'it did not',
        'nobody',
        'everyone',
    ];

    /** @var array<int, string> */
    private const DISAPPOINTED_PATTERNS = [
        'did not',
        "didn't",
        'failed',
        'nobody',
        'no one',
        'incorrect',
        'wrong',
        'illegal',
        'refused',
        'denied',
        'was not',
        "wasn't",
        'not allowed',
        'rejected',
    ];

    /** @var array<int, string> */
    private const MILD_CONCERN_PATTERNS = [
        'thought',
        'hoped',
        'expected',
        'would work',
        'seemed',
        'appeared',
        'assumed',
        'believed',
        'planned',
        'supposed',
        'intended',
    ];

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    public static function annotate(array $segments): array
    {
        $previousSpeech = null;

        $annotated = array_map(
            function (NarrationSegment $segment) use (&$previousSpeech): NarrationSegment {
                if ($segment->isPause()) {
                    return $segment;
                }

                $style = self::classify($segment->text, $previousSpeech);
                $params = KlausVoiceProfile::deliveryParams($style);
                $pauseBefore = 0.0;

                if ($segment->followsParagraphBreak) {
                    $pauseBefore += KlausVoiceProfile::paragraphBreakPause();
                }

                if ($style === KlausDeliveryStyle::Punchline) {
                    $pauseBefore += KlausVoiceProfile::prePunchlinePause();
                }

                $result = new NarrationSegment(
                    kind: $segment->kind,
                    text: $segment->text,
                    pauseSeconds: $segment->pauseSeconds,
                    followsLineBreak: $segment->followsLineBreak,
                    followsParagraphBreak: $segment->followsParagraphBreak,
                    isDramatic: $segment->isDramatic,
                    deliveryStyle: $style,
                    pauseBeforeSeconds: $pauseBefore,
                    pauseAfterSeconds: $params['pause_after'],
                    rate: $params['rate'],
                    pitch: $params['pitch'],
                    volume: $params['volume'],
                );

                $previousSpeech = $segment;

                return $result;
            },
            $segments,
        );

        return self::applyBookendPauses($annotated);
    }

    public static function classify(string $phrase, ?NarrationSegment $previousSpeech = null): KlausDeliveryStyle
    {
        $phrase = trim($phrase);

        if ($phrase === '') {
            return KlausDeliveryStyle::NeutralObservation;
        }

        if (KlausScriptBookends::matchesOutro($phrase)) {
            return KlausDeliveryStyle::BureaucraticClosure;
        }

        $lower = mb_strtolower($phrase);
        $wordCount = str_word_count($phrase);

        foreach (self::MILD_CONCERN_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return KlausDeliveryStyle::MildConcern;
            }
        }

        if (
            self::isDisappointedPhrase($lower)
            && ! ($previousSpeech !== null
                && self::isSetupPhrase($previousSpeech->text)
                && $wordCount <= 5
                && self::isPayoffPhrase($lower))
        ) {
            return KlausDeliveryStyle::Disappointed;
        }

        if (self::isPunchline($phrase, $previousSpeech)) {
            return KlausDeliveryStyle::Punchline;
        }

        return KlausDeliveryStyle::NeutralObservation;
    }

    public static function isPunchline(string $phrase, ?NarrationSegment $previousSpeech = null): bool
    {
        $phrase = trim($phrase);
        $wordCount = str_word_count($phrase);
        $lower = mb_strtolower($phrase);

        foreach (self::PUNCHLINE_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        if (
            $previousSpeech !== null
            && ! KlausScriptBookends::matchesIntro($previousSpeech->text)
            && ! KlausScriptBookends::matchesOutro($previousSpeech->text)
            && self::isSetupPhrase($previousSpeech->text)
            && $wordCount <= 5
        ) {
            return true;
        }

        if ($wordCount >= 1 && $wordCount <= 5 && $previousSpeech !== null && self::isShortDeadpanPhrase($phrase, $lower)) {
            return true;
        }

        return false;
    }

    public static function isShortDeadpanPhrase(string $phrase, ?string $lower = null): bool
    {
        $phrase = trim($phrase);

        if ($phrase === '' || self::isSetupPhrase($phrase)) {
            return false;
        }

        $lower ??= mb_strtolower($phrase);
        $wordCount = str_word_count($phrase);

        if ($wordCount < 1 || $wordCount > 5) {
            return false;
        }

        return NarrationScriptParser::isDramaticPhrase($phrase)
            || self::isPayoffPhrase($lower)
            || self::matchesPrefixList($lower, self::PUNCHLINE_PREFIXES);
    }

    private static function isDisappointedPhrase(string $lower): bool
    {
        foreach (self::DISAPPOINTED_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function isPayoffPhrase(string $lower): bool
    {
        foreach ([
            'did not',
            "didn't",
            'failed',
            'was not',
            "wasn't",
            'not work',
            'refused',
            'denied',
            'rejected',
        ] as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function isSetupPhrase(string $phrase): bool
    {
        $phrase = trim($phrase);

        if ($phrase === '') {
            return false;
        }

        return str_word_count($phrase) >= 4 && ! self::matchesPrefixList(mb_strtolower($phrase), self::PUNCHLINE_PREFIXES);
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    private static function matchesPrefixList(string $lower, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    private static function applyBookendPauses(array $segments): array
    {
        $bookendPause = KlausVoiceProfile::bookendPause();

        $speechIndices = [];

        foreach ($segments as $index => $segment) {
            if ($segment->isSpeech()) {
                $speechIndices[] = $index;
            }
        }

        foreach ($speechIndices as $position => $segmentIndex) {
            $segment = $segments[$segmentIndex];
            $pauseAfter = $segment->pauseAfterSeconds;
            $nextSpeechIndex = $speechIndices[$position + 1] ?? null;

            if ($position === 0 && KlausScriptBookends::matchesIntro($segment->text)) {
                $pauseAfter = max($pauseAfter, $bookendPause);
            }

            if (
                $nextSpeechIndex !== null
                && KlausScriptBookends::matchesOutro($segments[$nextSpeechIndex]->text)
            ) {
                $pauseAfter = max($pauseAfter, $bookendPause);
            }

            if ($pauseAfter === $segment->pauseAfterSeconds) {
                continue;
            }

            $segments[$segmentIndex] = new NarrationSegment(
                kind: $segment->kind,
                text: $segment->text,
                pauseSeconds: $segment->pauseSeconds,
                followsLineBreak: $segment->followsLineBreak,
                followsParagraphBreak: $segment->followsParagraphBreak,
                isDramatic: $segment->isDramatic,
                deliveryStyle: $segment->deliveryStyle,
                pauseBeforeSeconds: $segment->pauseBeforeSeconds,
                pauseAfterSeconds: $pauseAfter,
                rate: $segment->rate,
                pitch: $segment->pitch,
                volume: $segment->volume,
            );
        }

        return $segments;
    }
}
