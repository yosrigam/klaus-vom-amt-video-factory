<?php

namespace Tests\Unit;

use App\Support\HubPromptGenerationPrompt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubPromptGenerationPromptTest extends TestCase
{
    #[Test]
    public function it_renders_the_hub_prompt_for_an_idea(): void
    {
        $prompt = HubPromptGenerationPrompt::render('Sunday mowing tribunal');

        $this->assertStringContainsString('Sunday mowing tribunal', $prompt);
        $this->assertStringContainsString(config('klaus.disclaimer'), $prompt);
        $this->assertStringContainsString('English only', $prompt);
        $this->assertStringContainsString('Return valid JSON only', $prompt);
    }

    #[Test]
    public function it_returns_null_for_blank_idea_text(): void
    {
        $this->assertNull(HubPromptGenerationPrompt::render('   '));
    }
}
