<?php

namespace Tests\Unit;

use App\Support\HubIdeaBulkParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubIdeaBulkParserTest extends TestCase
{
    #[Test]
    public function it_parses_bracket_delimited_ideas_with_title_hook_and_concept(): void
    {
        $text = <<<'TEXT'
[[Title: Sunday Mowing Tribunal
Hook: Your lawn is a crime scene
Short concept: Klaus reviews neighbor complaints.]]

[[Title: Pfand Initiation
Hook: Welcome to Germany
Short concept: Klaus explains the bottle deposit ritual.]]
TEXT;

        $ideas = HubIdeaBulkParser::parse($text);

        $this->assertCount(2, $ideas);
        $this->assertSame('Sunday Mowing Tribunal', $ideas[0]['title']);
        $this->assertStringContainsString('Title: Sunday Mowing Tribunal', $ideas[0]['idea_text']);
        $this->assertStringContainsString('Premise:', $ideas[0]['idea_text']);
        $this->assertStringContainsString('Klaus reviews neighbor complaints.', $ideas[0]['idea_text']);
        $this->assertSame('Pfand Initiation', $ideas[1]['title']);
    }

    #[Test]
    public function it_splits_plain_ideas_by_blank_lines(): void
    {
        $text = "First raw idea\n\nSecond raw idea";

        $ideas = HubIdeaBulkParser::parse($text);

        $this->assertCount(2, $ideas);
        $this->assertSame('First raw idea', $ideas[0]['idea_text']);
        $this->assertSame('Second raw idea', $ideas[1]['idea_text']);
    }

    #[Test]
    public function it_splits_single_line_plain_ideas_when_no_blank_lines_exist(): void
    {
        $text = "Idea one\nIdea two\nIdea three";

        $ideas = HubIdeaBulkParser::parse($text);

        $this->assertCount(3, $ideas);
        $this->assertSame('Idea two', $ideas[1]['idea_text']);
    }

    #[Test]
    public function it_rejects_empty_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HubIdeaBulkParser::parse('   ');
    }
}
