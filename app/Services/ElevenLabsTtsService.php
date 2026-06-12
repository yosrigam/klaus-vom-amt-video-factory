<?php

namespace App\Services;

use App\Services\Concerns\SynthesizesPhrasesToAudio;
use App\Support\KlausScriptBookends;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ElevenLabsTtsService
{
    use SynthesizesPhrasesToAudio;

    protected function synthesizePhraseAudio(
        string $text,
        string $absoluteOutputPath,
        int $phraseIndex,
        int $phraseCount,
        string $phrase,
    ): void {
        $apiKey = config('services.elevenlabs.api_key');
        $voiceId = $this->voiceIdForPhrase($phraseIndex, $phraseCount, $phrase);

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($voiceId) || $voiceId === '') {
            throw new RuntimeException('ElevenLabs API key and voice ID are required.');
        }

        $response = Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Accept' => 'audio/mpeg',
            'Content-Type' => 'application/json',
        ])->timeout(180)->post(
            'https://api.elevenlabs.io/v1/text-to-speech/'.$voiceId,
            [
                'text' => $text,
                'model_id' => config('services.elevenlabs.model', 'eleven_multilingual_v2'),
                'voice_settings' => [
                    'stability' => (float) config('services.elevenlabs.stability', 0.45),
                    'similarity_boost' => (float) config('services.elevenlabs.similarity_boost', 0.8),
                    'style' => (float) config('services.elevenlabs.style', 0.15),
                    'use_speaker_boost' => true,
                ],
            ],
        );

        if (! $response->successful()) {
            throw new RuntimeException('ElevenLabs TTS failed: '.$response->body());
        }

        if (file_put_contents($absoluteOutputPath, $response->body()) === false) {
            throw new RuntimeException('Failed to write ElevenLabs audio file.');
        }
    }

    protected function voiceIdForPhrase(int $index, int $totalPhrases, string $phrase): ?string
    {
        $mainVoice = config('services.elevenlabs.voice_id');
        $introVoice = config('services.elevenlabs.intro_voice_id', $mainVoice);

        if ($index === 0) {
            return is_string($introVoice) ? $introVoice : null;
        }

        if (KlausScriptBookends::matchesOutro($phrase)) {
            return is_string($introVoice) ? $introVoice : null;
        }

        return is_string($mainVoice) ? $mainVoice : null;
    }
}
