<?php

namespace App\Services;

use App\Enums\NarrationStyle;
use RuntimeException;

class VoiceSynthesisService
{
    public function synthesize(
        string $text,
        string $directory = 'klaus/audio',
        ?NarrationStyle $style = null,
    ): string {
        $style ??= NarrationStyle::fromConfig();
        $driver = config('klaus.tts_driver', 'edge');

        if ($driver === 'elevenlabs' && $this->elevenLabsConfigured()) {
            return app(ElevenLabsTtsService::class)->synthesize($text, $directory, $style);
        }

        if ($driver === 'elevenlabs' && ! $this->elevenLabsConfigured()) {
            throw new RuntimeException('KLAUS_TTS_DRIVER=elevenlabs but ELEVENLABS_API_KEY or ELEVENLABS_VOICE_ID is missing.');
        }

        return app(EdgeTtsService::class)->synthesize($text, $directory, $style);
    }

    public function driverLabel(): string
    {
        return config('klaus.tts_driver', 'edge') === 'elevenlabs' && $this->elevenLabsConfigured()
            ? 'elevenlabs'
            : 'edge-tts';
    }

    protected function elevenLabsConfigured(): bool
    {
        $key = config('services.elevenlabs.api_key');
        $voice = config('services.elevenlabs.voice_id');

        return is_string($key) && $key !== '' && is_string($voice) && $voice !== '';
    }
}
