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

        $this->assertStringContainsString('Klaus vom Amt', $parsed['script']);
        $this->assertStringContainsString('sponge', $parsed['image_prompt']);
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
