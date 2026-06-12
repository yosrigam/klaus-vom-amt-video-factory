<?php

namespace App\Services;

use App\Services\Concerns\SynthesizesPhrasesToAudio;
use App\Support\KlausScriptBookends;
use App\Support\TtsVoice;
use RuntimeException;
use Symfony\Component\Process\Process;

class EdgeTtsService
{
    use SynthesizesPhrasesToAudio;

    protected function synthesizePhraseAudio(
        string $text,
        string $absoluteOutputPath,
        int $phraseIndex,
        int $phraseCount,
        string $phrase,
    ): void {
        $profile = $this->narrationProfile;

        $command = [
            config('klaus.edge_tts_path'),
            '--voice', $this->voiceForPhrase($phraseIndex, $phraseCount, $phrase),
            '--text', $text,
            '--write-media', $absoluteOutputPath,
        ];

        foreach ([
            'rate' => $profile?->rate ?? config('klaus.edge_tts_rate'),
            'pitch' => $profile?->pitch ?? config('klaus.edge_tts_pitch'),
            'volume' => $profile?->volume ?? config('klaus.edge_tts_volume'),
        ] as $option => $value) {
            if (is_string($value) && $value !== '') {
                $command[] = "--{$option}={$value}";
            }
        }

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('edge-tts failed: '.$process->getErrorOutput().$process->getOutput());
        }
    }

    protected function voiceForPhrase(int $index, int $totalPhrases, string $phrase): string
    {
        $germanVoice = (string) config('klaus.edge_tts_intro_voice', TtsVoice::main());
        $mainVoice = TtsVoice::main();
        $introPhraseCount = (int) config('klaus.edge_tts_intro_phrase_count', 1);

        if ($index < $introPhraseCount) {
            return $germanVoice;
        }

        if (KlausScriptBookends::matchesOutro($phrase)) {
            return $germanVoice;
        }

        return $mainVoice;
    }
}
