<?php

namespace Tests\Unit;

use App\Support\KlausImagePrompt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KlausImagePromptTest extends TestCase
{
    #[Test]
    public function it_builds_full_prompt_with_character_lock_and_scene(): void
    {
        $full = KlausImagePrompt::buildFull('Klaus beside a dripping sponge, solid orange background.');

        $this->assertStringContainsString('CHARACTER BIBLE — KLAUS VOM AMT V1', $full);
        $this->assertStringContainsString('STYLE GUIDE', $full);
        $this->assertStringContainsString('SCENE:', $full);
        $this->assertStringContainsString('dripping sponge', $full);
    }

    #[Test]
    public function it_does_not_double_prepend_lock_for_api(): void
    {
        $scene = 'Klaus beside a dripping sponge.';
        $full = KlausImagePrompt::buildFull($scene);
        $forApi = KlausImagePrompt::buildForApi($full);

        $this->assertSame($full, $forApi);
        $this->assertSame(1, substr_count($forApi, 'CHARACTER BIBLE — KLAUS VOM AMT V1'));
    }
}
