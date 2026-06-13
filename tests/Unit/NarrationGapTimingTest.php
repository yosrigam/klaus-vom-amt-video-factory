<?php

namespace Tests\Unit;

use App\Enums\KlausDeliveryStyle;
use App\Services\Concerns\SynthesizesPhrasesToAudio;
use App\Support\NarrationSegment;
use App\Support\NarrationSegmentKind;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NarrationGapTimingTest extends TestCase
{
    #[Test]
    public function it_uses_the_larger_of_pause_after_and_pause_before_not_both(): void
    {
        $synth = new class
        {
            use SynthesizesPhrasesToAudio;

            public function gap(NarrationSegment $segment, ?NarrationSegment $previous): float
            {
                return $this->gapBeforeSpeech($segment, $previous);
            }

            protected function synthesizePhraseAudio(
                string $text,
                string $absoluteOutputPath,
                int $phraseIndex,
                int $phraseCount,
                string $phrase,
            ): void {}
        };

        $first = new NarrationSegment(
            kind: NarrationSegmentKind::Speech,
            text: 'First line.',
            deliveryStyle: KlausDeliveryStyle::NeutralObservation,
            pauseAfterSeconds: 0.08,
        );

        $second = new NarrationSegment(
            kind: NarrationSegmentKind::Speech,
            text: 'Second line.',
            followsLineBreak: true,
            deliveryStyle: KlausDeliveryStyle::NeutralObservation,
            pauseBeforeSeconds: 0.0,
            pauseAfterSeconds: 0.08,
        );

        $this->assertSame(0.0, $synth->gap($first, null));
        $this->assertSame(0.08, $synth->gap($second, $first));
    }
}
