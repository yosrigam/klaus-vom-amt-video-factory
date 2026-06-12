<?php

namespace App\Support;

use App\Enums\KlausDeliveryStyle;

final class KlausVoiceProfile
{
    public const DEFAULT = [
        'voice' => 'en-GB-ThomasNeural',
        'intro_voice' => 'de-DE-ConradNeural',
        'base_rate' => '+2%',
        'base_pitch' => '-3Hz',
        'base_volume' => '+0%',
        'merge_short_phrases' => false,
        'sentence_pause' => 0.30,
        'short_phrase_pause' => 0.18,
        'bookend_pause' => 0.70,
        'pre_punchline_pause' => 0.35,
        'punchline_pause' => 0.75,
        'paragraph_break_pause' => 0.75,
        'line_break_pause' => 0.12,
    ];

    /**
     * @return array{rate: string, pitch: string, volume: string, pause_after: float}
     */
    public static function deliveryParams(KlausDeliveryStyle $style): array
    {
        $configured = config("klaus.voice_profile.delivery_styles.{$style->value}");

        if (is_array($configured)) {
            return self::normalizeDeliveryParams([
                'rate' => (string) ($configured['rate'] ?? self::DEFAULT['base_rate']),
                'pitch' => (string) ($configured['pitch'] ?? self::DEFAULT['base_pitch']),
                'volume' => (string) ($configured['volume'] ?? self::DEFAULT['base_volume']),
                'pause_after' => (float) ($configured['pause_after'] ?? self::DEFAULT['sentence_pause']),
            ]);
        }

        return self::normalizeDeliveryParams(match ($style) {
            KlausDeliveryStyle::NeutralObservation => [
                'rate' => '-2%',
                'pitch' => '-2Hz',
                'volume' => '+0%',
                'pause_after' => 0.28,
            ],
            KlausDeliveryStyle::MildConcern => [
                'rate' => '-4%',
                'pitch' => '-1Hz',
                'volume' => '+0%',
                'pause_after' => 0.32,
            ],
            KlausDeliveryStyle::Disappointed => [
                'rate' => '-6%',
                'pitch' => '-4Hz',
                'volume' => '-1%',
                'pause_after' => 0.38,
            ],
            KlausDeliveryStyle::Punchline => [
                'rate' => '-8%',
                'pitch' => '-5Hz',
                'volume' => '+1%',
                'pause_after' => 0.55,
            ],
            KlausDeliveryStyle::BureaucraticClosure => [
                'rate' => '-10%',
                'pitch' => '-4Hz',
                'volume' => '+0%',
                'pause_after' => 0.65,
            ],
        });
    }

    public static function scalePause(float $seconds): float
    {
        $multiplier = (float) config('klaus.edge_tts_pause_multiplier', 1.0);

        return round(max(0.0, $seconds * $multiplier), 3);
    }

    public static function prePunchlinePause(): float
    {
        return self::scalePause((float) config(
            'klaus.voice_profile.pre_punchline_pause',
            self::DEFAULT['pre_punchline_pause'],
        ));
    }

    public static function paragraphBreakPause(): float
    {
        return self::scalePause((float) config(
            'klaus.voice_profile.paragraph_break_pause',
            self::DEFAULT['paragraph_break_pause'],
        ));
    }

    public static function lineBreakPause(): float
    {
        return self::scalePause((float) config(
            'klaus.voice_profile.line_break_pause',
            self::DEFAULT['line_break_pause'] ?? 0.12,
        ));
    }

    public static function bookendPause(): float
    {
        return self::scalePause((float) config(
            'klaus.voice_profile.bookend_pause',
            self::DEFAULT['bookend_pause'],
        ));
    }

    /**
     * @param  array{rate: string, pitch: string, volume: string, pause_after: float}  $params
     * @return array{rate: string, pitch: string, volume: string, pause_after: float}
     */
    private static function normalizeDeliveryParams(array $params): array
    {
        $sentencePause = (float) config('klaus.voice_profile.sentence_pause', self::DEFAULT['sentence_pause']);

        return [
            'rate' => TtsRate::withGlobalOffset($params['rate']),
            'pitch' => $params['pitch'],
            'volume' => $params['volume'],
            'pause_after' => self::scalePause(max($params['pause_after'], $sentencePause)),
        ];
    }
}
