<?php

namespace App\Support;

class TtsVoice
{
    public static function main(): string
    {
        $direct = config('klaus.edge_tts_voice');

        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $variant = (string) config('klaus.edge_tts_voice_variant', 'a');
        $voices = config('klaus.edge_tts_voices', []);

        if (is_array($voices) && isset($voices[$variant]) && is_string($voices[$variant])) {
            return $voices[$variant];
        }

        return (string) config('klaus.edge_tts_voice_a', 'en-US-GuyNeural');
    }
}
