<?php

namespace Tests\Unit;

use App\Services\EdgeTtsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EdgeTtsVoiceSelectionTest extends TestCase
{
    #[Test]
    public function it_uses_german_voice_for_every_outro_phrase_not_only_the_last_one(): void
    {
        config([
            'klaus.edge_tts_intro_voice' => 'de-DE-ConradNeural',
            'klaus.edge_tts_voice' => 'en-GB-RyanNeural',
            'klaus.edge_tts_intro_phrase_count' => 1,
        ]);

        $service = new class extends EdgeTtsService
        {
            public function resolveVoice(int $index, int $totalPhrases, string $phrase): string
            {
                return $this->voiceForPhrase($index, $totalPhrases, $phrase);
            }
        };

        $this->assertSame('de-DE-ConradNeural', $service->resolveVoice(0, 25, 'Klaus vom Amt hier.'));
        $this->assertSame('en-GB-RyanNeural', $service->resolveVoice(10, 25, 'You washed your car on a Sunday.'));
        $this->assertSame('de-DE-ConradNeural', $service->resolveVoice(23, 25, 'Der Vorgang ist abgeschlossen.'));
        $this->assertSame('de-DE-ConradNeural', $service->resolveVoice(24, 25, 'Auf Wiedersehen.'));
    }
}
