<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class EdgeTtsService
{
    public function synthesize(string $text, string $directory = 'klaus/audio'): string
    {
        $path = $directory.'/'.uniqid('voice_', true).'.mp3';
        $absolutePath = Storage::disk('local')->path($path);

        Storage::disk('local')->makeDirectory($directory);

        $process = new Process([
            config('klaus.edge_tts_path'),
            '--voice', config('klaus.edge_tts_voice'),
            '--text', $text,
            '--write-media', $absolutePath,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($absolutePath)) {
            throw new RuntimeException('edge-tts failed: '.$process->getErrorOutput().$process->getOutput());
        }

        return $path;
    }
}
