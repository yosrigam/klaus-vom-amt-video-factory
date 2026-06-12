<?php

namespace Tests\Unit;

use App\Support\KlausScriptBookends;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KlausScriptBookendsTest extends TestCase
{
    #[Test]
    public function it_applies_intro_body_and_outro_with_line_breaks(): void
    {
        $script = KlausScriptBookends::apply("He washed his car.\n\nOn a Sunday.");

        $this->assertStringStartsWith("Klaus vom Amt hier.\n\n", $script);
        $this->assertStringEndsWith("\n\nDer Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.", $script);
        $this->assertStringContainsString('He washed his car.', $script);
    }

    #[Test]
    public function it_strips_duplicate_bookends_before_reapplying(): void
    {
        $script = KlausScriptBookends::apply(
            "Klaus vom Amt hier.\n\nHe washed his car.\n\nAuf Wiedersehen.",
        );

        $this->assertSame(1, substr_count($script, 'Klaus vom Amt hier.'));
        $this->assertSame(1, substr_count($script, 'Der Vorgang ist abgeschlossen.'));
        $this->assertSame(1, substr_count($script, 'Auf Wiedersehen.'));
    }

    #[Test]
    public function it_strips_english_outro_before_applying_german_bookends(): void
    {
        $script = KlausScriptBookends::apply(
            "He washed his car on a Sunday.\n\nThe process is complete.",
        );

        $this->assertStringNotContainsString('The process is complete', $script);
        $this->assertSame(1, substr_count($script, 'Der Vorgang ist abgeschlossen.'));
        $this->assertStringEndsWith("\n\nDer Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.", $script);
    }

    #[Test]
    public function it_strips_english_outro_lines_from_the_body_even_before_german_bookends(): void
    {
        $body = KlausScriptBookends::sanitizeBody(
            "He washed his car.\n\nThe process is complete.\n\nDer Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.",
        );

        $this->assertSame('He washed his car.', $body);
    }

    #[Test]
    public function it_matches_the_new_outro_for_german_voice(): void
    {
        $this->assertTrue(KlausScriptBookends::matchesOutro('Der Vorgang ist abgeschlossen.'));
        $this->assertTrue(KlausScriptBookends::matchesOutro('Vorgang abgeschlossen.'));
        $this->assertTrue(KlausScriptBookends::matchesOutro('Auf Wiedersehen.'));
    }
}
