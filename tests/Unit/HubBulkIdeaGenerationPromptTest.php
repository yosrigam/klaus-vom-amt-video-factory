<?php

namespace Tests\Unit;

use App\Support\HubBulkIdeaGenerationPrompt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubBulkIdeaGenerationPromptTest extends TestCase
{
    #[Test]
    public function it_renders_a_bulk_idea_generation_prompt_for_a_pillar(): void
    {
        $pillarKey = array_key_first(config('content_pillars', []));

        $this->assertNotNull($pillarKey);

        $prompt = HubBulkIdeaGenerationPrompt::render($pillarKey);

        $this->assertNotNull($prompt);
        $this->assertStringContainsString('Generate exactly 10', $prompt);
        $this->assertStringContainsString('Title:', $prompt);
        $this->assertStringContainsString('Hook:', $prompt);
        $this->assertStringContainsString('Short concept', $prompt);
    }

    #[Test]
    public function it_returns_null_without_a_pillar(): void
    {
        $this->assertNull(HubBulkIdeaGenerationPrompt::render(null));
    }
}
