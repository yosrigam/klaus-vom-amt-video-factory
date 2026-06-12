<?php

namespace App\Support;

use App\Enums\KlausDeliveryStyle;

final readonly class PhraseDelivery
{
    public function __construct(
        public KlausDeliveryStyle $style,
        public string $rate,
        public string $pitch,
        public string $volume,
        public float $pauseBeforeSeconds,
        public float $pauseAfterSeconds,
        public string $voice,
    ) {}

    public static function fromSegment(NarrationSegment $segment, string $voice): self
    {
        $style = $segment->deliveryStyle ?? KlausDeliveryStyle::NeutralObservation;
        $params = KlausVoiceProfile::deliveryParams($style);

        return new self(
            style: $style,
            rate: $segment->rate ?? $params['rate'],
            pitch: $segment->pitch ?? $params['pitch'],
            volume: $segment->volume ?? $params['volume'],
            pauseBeforeSeconds: $segment->pauseBeforeSeconds,
            pauseAfterSeconds: $segment->pauseAfterSeconds > 0
                ? $segment->pauseAfterSeconds
                : $params['pause_after'],
            voice: $voice,
        );
    }
}
