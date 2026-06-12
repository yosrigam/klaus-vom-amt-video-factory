<?php

namespace App\Support;

use App\Enums\KlausDeliveryStyle;

enum NarrationSegmentKind: string
{
    case Speech = 'speech';
    case Pause = 'pause';
}

final readonly class NarrationSegment
{
    public function __construct(
        public NarrationSegmentKind $kind,
        public string $text = '',
        public float $pauseSeconds = 0.0,
        public bool $followsLineBreak = false,
        public bool $followsParagraphBreak = false,
        public bool $isDramatic = false,
        public ?KlausDeliveryStyle $deliveryStyle = null,
        public float $pauseBeforeSeconds = 0.0,
        public float $pauseAfterSeconds = 0.0,
        public ?string $rate = null,
        public ?string $pitch = null,
        public ?string $volume = null,
    ) {}

    public function isSpeech(): bool
    {
        return $this->kind === NarrationSegmentKind::Speech;
    }

    public function isPause(): bool
    {
        return $this->kind === NarrationSegmentKind::Pause;
    }
}
