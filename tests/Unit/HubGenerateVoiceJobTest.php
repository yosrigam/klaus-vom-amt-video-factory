<?php

namespace Tests\Unit;

use App\Enums\HubIdeaStatus;
use App\Jobs\HubGenerateVoiceJob;
use App\Models\HubIdea;
use App\Services\VoiceSynthesisService;
use App\Support\KlausScriptBookends;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubGenerateVoiceJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_english_outro_from_the_voice_script_before_synthesis(): void
    {
        config(['klaus.narration_style' => 'default']);

        $hubIdea = HubIdea::query()->create([
            'idea_text' => 'Sunday car wash',
            'title' => 'Sunday car wash',
            'script' => "Klaus vom Amt hier.\n\nHe washed his car on a Sunday.\n\nThe process is complete.\n\nDer Vorgang ist abgeschlossen.\n\nAuf Wiedersehen.",
            'status' => HubIdeaStatus::ContentReady,
        ]);

        $tts = Mockery::mock(VoiceSynthesisService::class);
        $tts->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text): bool {
                return ! str_contains($text, 'The process is complete')
                    && substr_count($text, 'Der Vorgang ist abgeschlossen.') === 1;
            })
            ->andReturn('klaus/audio/test_voice.mp3');

        $this->app->instance(VoiceSynthesisService::class, $tts);

        (new HubGenerateVoiceJob($hubIdea))->handle($tts);

        $hubIdea->refresh();

        $this->assertStringNotContainsString('The process is complete', $hubIdea->script);
        $this->assertSame(1, substr_count($hubIdea->script, KlausScriptBookends::outroLines()[0]));
        $this->assertSame(HubIdeaStatus::VoiceReady, $hubIdea->status);
    }
}
