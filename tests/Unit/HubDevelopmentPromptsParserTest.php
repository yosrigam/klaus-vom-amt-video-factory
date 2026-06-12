<?php

namespace Tests\Unit;

use App\Support\HubDevelopmentPromptsParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubDevelopmentPromptsParserTest extends TestCase
{
    #[Test]
    public function it_parses_script_and_image_prompt_json(): void
    {
        $json = <<<'JSON'
{
  "script": "Klaus vom Amt here. Unfortunately, your sponge usage requires review.",
  "image_prompt": "Klaus holding a clipboard beside a dripping sponge, solid orange background."
}
JSON;

        $parsed = HubDevelopmentPromptsParser::parse($json);

        $this->assertSame('Unfortunately, your sponge usage requires review.', $parsed['script']);
        $this->assertStringContainsString('sponge', $parsed['image_prompt']);
    }

    #[Test]
    public function it_strips_manually_added_german_bookends_from_pasted_json(): void
    {
        $json = <<<'JSON'
{
  "script": "Klaus vom Amt hier.\n\nMost people think a discarded deposit bottle is trash.\n\nThat is what economists call opportunity.\n\nDer Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.",
  "image_prompt": "Klaus with bottles on pedestals, solid turquoise background."
}
JSON;

        $parsed = HubDevelopmentPromptsParser::parse($json);

        $this->assertStringNotContainsString('Der Vorgang ist abgeschlossen', $parsed['script']);
        $this->assertStringNotContainsString('Auf Wiedersehen', $parsed['script']);
        $this->assertStringNotContainsString('Klaus vom Amt hier', $parsed['script']);
        $this->assertStringContainsString('economists call opportunity', $parsed['script']);
    }

    #[Test]
    public function it_rejects_incomplete_json(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HubDevelopmentPromptsParser::parse('{"script":"only script"}');
    }

    #[Test]
    public function it_rejects_legacy_json_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HubDevelopmentPromptsParser::parse('{"concept_prompt":"x","script_prompt":"y","image_prompt_instruction":"z"}');
    }
}
