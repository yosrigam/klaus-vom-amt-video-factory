<?php

namespace Tests\Unit;

use App\Support\BulkVideoIdeaParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulkVideoIdeaParserTest extends TestCase
{
    #[Test]
    public function it_extracts_every_star_delimited_idea(): void
    {
        $text = <<<'TEXT'
**Title: First Idea
Hook: Opening line one
Short concept: Concept one**

**Title: Second Idea
Hook: Opening line two
Short concept: Concept two**
TEXT;

        $ideas = BulkVideoIdeaParser::parse($text);

        $this->assertCount(2, $ideas);
        $this->assertSame('First Idea', $ideas[0]['title']);
        $this->assertSame('Opening line one', $ideas[0]['hook']);
        $this->assertSame('Concept one', $ideas[0]['short_concept']);
        $this->assertSame('Second Idea', $ideas[1]['title']);
    }

    #[Test]
    public function it_parses_multiline_blocks_and_line_based_fallbacks(): void
    {
        $text = <<<'TEXT'
**Sunday Mowing Tribunal
Your lawn is a crime scene
Klaus reviews neighbor complaints about Sunday trimming.**

**Title: Pfand Initiation
Hook: Welcome to Germany
Short concept: Klaus explains the sacred bottle deposit ritual across three painful steps.**
TEXT;

        $ideas = BulkVideoIdeaParser::parse($text);

        $this->assertCount(2, $ideas);
        $this->assertSame('Sunday Mowing Tribunal', $ideas[0]['title']);
        $this->assertSame('Your lawn is a crime scene', $ideas[0]['hook']);
        $this->assertSame('Klaus reviews neighbor complaints about Sunday trimming.', $ideas[0]['short_concept']);
        $this->assertSame('Pfand Initiation', $ideas[1]['title']);
    }

    #[Test]
    public function it_ignores_empty_blocks(): void
    {
        $ideas = BulkVideoIdeaParser::parse("****\n**Title: Only One\nHook: Hook\nShort concept: Concept**");

        $this->assertCount(1, $ideas);
        $this->assertSame('Only One', $ideas[0]['title']);
    }
}
