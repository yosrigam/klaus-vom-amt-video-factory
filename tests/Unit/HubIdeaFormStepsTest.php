<?php

namespace Tests\Unit;

use App\Models\HubIdea;
use App\Support\HubIdeaFormSteps;
use Filament\Schemas\Components\Utilities\Get;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubIdeaFormStepsTest extends TestCase
{
    #[Test]
    public function it_unlocks_steps_progressively(): void
    {
        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->andReturn(null);

        $this->assertFalse(HubIdeaFormSteps::ideaComplete($get, null));

        $record = new HubIdea(['idea_text' => 'Sunday car wash']);

        $this->assertTrue(HubIdeaFormSteps::ideaComplete($get, $record));
        $this->assertFalse(HubIdeaFormSteps::contentComplete($get, $record));

        $record->fill([
            'script' => 'Voiceover',
            'image_prompt' => 'Scene prompt',
        ]);

        $this->assertTrue(HubIdeaFormSteps::contentComplete($get, $record));
        $this->assertFalse(HubIdeaFormSteps::imageComplete($get, $record));

        $record->image_path = 'klaus/images/test.png';

        $this->assertTrue(HubIdeaFormSteps::productionUnlocked($get, $record));
    }
}
