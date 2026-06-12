<?php

namespace Tests\Unit;

use App\Enums\NarrationStyle;
use App\Support\AwkwardNarrationFormatter;
use App\Support\NarrationProfile;
use App\Support\NarrationScriptParser;
use App\Support\NarrationSegmentKind;
use App\Support\ScriptPhrases;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NarrationScriptParserTest extends TestCase
{
    #[Test]
    public function it_preserves_line_breaks_as_separate_chunks(): void
    {
        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = NarrationScriptParser::parse(
            "This is a man.\n\nHe had a plan.\n\nIt failed.",
            $profile,
        );

        $speech = array_values(array_filter(
            $segments,
            static fn ($segment) => $segment->isSpeech(),
        ));

        $this->assertCount(3, $speech);
        $this->assertSame('This is a man.', $speech[0]->text);
        $this->assertTrue($speech[1]->followsLineBreak);
        $this->assertSame('It failed.', $speech[2]->text);
    }

    #[Test]
    public function it_extracts_beat_markers_as_silent_pauses(): void
    {
        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = NarrationScriptParser::parse(
            "This seemed like a good idea.\n\n[pause]\n\nIt wasn't.",
            $profile,
        );

        $pause = collect($segments)->first(static fn ($segment) => $segment->isPause());

        $this->assertNotNull($pause);
        $this->assertSame(0.45, $pause->pauseSeconds);
    }

    #[Test]
    public function it_marks_short_punchlines_as_dramatic(): void
    {
        $this->assertTrue(NarrationScriptParser::isDramaticPhrase('Completely.'));
        $this->assertTrue(NarrationScriptParser::isDramaticPhrase('It did not.'));
        $this->assertFalse(NarrationScriptParser::isDramaticPhrase('Everyone thought it would work.'));
    }

    #[Test]
    public function beat_markers_are_not_included_in_speech_segments(): void
    {
        $profile = NarrationProfile::for(NarrationStyle::Awkward);
        $segments = NarrationScriptParser::parse("He had one job.\n\n[beat]\n\nHe failed.", $profile);

        foreach ($segments as $segment) {
            if ($segment->kind === NarrationSegmentKind::Speech) {
                $this->assertStringNotContainsString('[beat]', $segment->text);
            }
        }
    }
}

class AwkwardNarrationFormatterTest extends TestCase
{
    #[Test]
    public function it_breaks_long_sentences_into_short_lines(): void
    {
        $formatted = AwkwardNarrationFormatter::format(
            'A man attempted to park his vehicle in a restricted area and immediately received a fine.',
        );

        $lines = array_values(array_filter(explode("\n\n", $formatted)));

        $this->assertGreaterThan(1, count($lines));
        $this->assertTrue(collect($lines)->every(static fn (string $line) => mb_strlen($line) <= 80));
    }
}

class ScriptPhrasesTest extends TestCase
{
    #[Test]
    public function it_merges_short_interrogation_beats(): void
    {
        $phrases = ScriptPhrases::mergeShortPhrases([
            'Tell me exactly when you touched the sponge.',
            'Mhmm.',
            '10:14 in the morning.',
            'On a Sunday.',
            'Excellent.',
        ]);

        $this->assertSame([
            'Tell me exactly when you touched the sponge.',
            'Mhmm. 10:14 in the morning.',
            'On a Sunday. Excellent.',
        ], $phrases);
    }

    #[Test]
    public function it_keeps_outro_separate(): void
    {
        $phrases = ScriptPhrases::mergeShortPhrases([
            'As am I.',
            'Der Vorgang ist abgeschlossen.',
        ]);

        $this->assertSame([
            'As am I.',
            'Der Vorgang ist abgeschlossen.',
        ], $phrases);
    }
}
