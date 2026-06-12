<?php

namespace App\Support;

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
        public bool $isDramatic = false,
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
