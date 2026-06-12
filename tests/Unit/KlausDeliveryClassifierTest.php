<?php

namespace Tests\Unit;

use App\Enums\KlausDeliveryStyle;
use App\Enums\NarrationStyle;
use App\Support\KlausDeliveryClassifier;
use App\Support\KlausScriptBookends;
use App\Support\NarrationProfile;
use App\Support\NarrationScriptParser;
use App\Support\NarrationSegment;
use App\Support\NarrationSegmentKind;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KlausDeliveryClassifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'klaus.edge_tts_rate' => '+0%',
            'klaus.edge_tts_pause_multiplier' => 1.0,
        ]);
    }

    #[Test]
    public function it_classifies_example_phrases(): void
    {
        $this->assertSame(
            KlausDeliveryStyle::NeutralObservation,
            KlausDeliveryClassifier::classify('This is a man.'),
        );

        $this->assertSame(
            KlausDeliveryStyle::MildConcern,
            KlausDeliveryClassifier::classify('He thought this would work.'),
        );

        $setup = new NarrationSegment(NarrationSegmentKind::Speech, 'He thought this would work.');

        $this->assertSame(
            KlausDeliveryStyle::Punchline,
            KlausDeliveryClassifier::classify('It did not.', $setup),
        );

        $this->assertSame(
            KlausDeliveryStyle::Disappointed,
            KlausDeliveryClassifier::classify('Nobody checked.'),
        );

        $this->assertSame(
            KlausDeliveryStyle::BureaucraticClosure,
            KlausDeliveryClassifier::classify('Der Vorgang ist abgeschlossen.'),
        );
    }

    #[Test]
    public function it_detects_punchlines_after_setup_lines(): void
    {
        $setup = new NarrationSegment(NarrationSegmentKind::Speech, 'He had one job.');

        $this->assertTrue(KlausDeliveryClassifier::isPunchline('He did not do it.', $setup));
    }

    #[Test]
    public function it_annotates_segments_with_delivery_parameters(): void
    {
        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = KlausDeliveryClassifier::annotate(NarrationScriptParser::parse(
            "He had a plan.\n\nThe plan was illegal.",
            $profile,
        ));

        $speech = array_values(array_filter(
            $segments,
            static fn ($segment) => $segment->isSpeech(),
        ));

        $this->assertSame(KlausDeliveryStyle::NeutralObservation, $speech[0]->deliveryStyle);
        $this->assertSame(KlausDeliveryStyle::Disappointed, $speech[1]->deliveryStyle);
        $this->assertSame(0.12, $speech[1]->pauseBeforeSeconds);
        $this->assertSame('-6%', $speech[1]->rate);
    }

    #[Test]
    public function it_adds_pre_punchline_pause_after_paragraph_break(): void
    {
        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = KlausDeliveryClassifier::annotate(NarrationScriptParser::parse(
            "He had one job.\n\nHe did not do it.",
            $profile,
        ));

        $punchline = collect($segments)->last(static fn ($segment) => $segment->isSpeech());

        $this->assertSame(KlausDeliveryStyle::Punchline, $punchline->deliveryStyle);
        $this->assertEqualsWithDelta(0.34, $punchline->pauseBeforeSeconds, 0.001);
        $this->assertSame('-8%', $punchline->rate);
        $this->assertSame('-5Hz', $punchline->pitch);
    }

    #[Test]
    public function it_classifies_short_deadpan_phrases_as_punchlines_after_setup(): void
    {
        $setup = new NarrationSegment(NarrationSegmentKind::Speech, 'He had one job.');

        $this->assertSame(
            KlausDeliveryStyle::Punchline,
            KlausDeliveryClassifier::classify('Completely.', $setup),
        );

        $this->assertSame(
            KlausDeliveryStyle::Punchline,
            KlausDeliveryClassifier::classify('Excellent.', $setup),
        );

        $this->assertSame(
            KlausDeliveryStyle::NeutralObservation,
            KlausDeliveryClassifier::classify('Completely.'),
        );

        $this->assertSame(
            KlausDeliveryStyle::NeutralObservation,
            KlausDeliveryClassifier::classify('This is a man.'),
        );
    }

    #[Test]
    public function it_adds_bookend_pause_after_intro_and_before_outro(): void
    {
        config(['klaus.voice_profile.bookend_pause' => 0.70]);

        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = KlausDeliveryClassifier::annotate(NarrationScriptParser::parse(
            KlausScriptBookends::apply('He had a plan.'),
            $profile,
        ));

        $speech = array_values(array_filter(
            $segments,
            static fn ($segment) => $segment->isSpeech(),
        ));

        $this->assertSame(0.70, $speech[0]->pauseAfterSeconds);

        $bodyBeforeOutro = $speech[count($speech) - count(KlausScriptBookends::outroLines()) - 1];
        $this->assertSame(0.70, $bodyBeforeOutro->pauseAfterSeconds);
        $this->assertSame(KlausDeliveryStyle::NeutralObservation, $bodyBeforeOutro->deliveryStyle);

        $finalOutro = $speech[count($speech) - 1];
        $this->assertSame(KlausDeliveryStyle::BureaucraticClosure, $finalOutro->deliveryStyle);
        $this->assertSame('-10%', $finalOutro->rate);
    }

    #[Test]
    public function it_marks_outro_lines_as_bureaucratic_closure(): void
    {
        foreach (KlausScriptBookends::outroLines() as $outroLine) {
            $this->assertSame(
                KlausDeliveryStyle::BureaucraticClosure,
                KlausDeliveryClassifier::classify($outroLine),
            );
        }
    }
}
