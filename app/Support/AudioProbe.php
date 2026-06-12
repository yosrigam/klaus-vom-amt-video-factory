<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class AudioProbe
{
    public static function durationSeconds(string $absolutePath): ?float
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $process = new Process([
            self::ffprobePath(),
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absolutePath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $duration = (float) trim($process->getOutput());

        return $duration > 0 ? $duration : null;
    }

    public static function timingSidecarPath(string $audioRelativePath): string
    {
        return preg_replace('/\.mp3$/i', '.timing.json', $audioRelativePath) ?? $audioRelativePath.'.timing.json';
    }

    public static function ffprobePath(): string
    {
        $ffmpeg = config('klaus.ffmpeg_path', 'ffmpeg');
        $candidate = preg_replace('/ffmpeg$/', 'ffprobe', $ffmpeg);

        if (is_string($candidate) && is_executable($candidate)) {
            return $candidate;
        }

        throw new RuntimeException('ffprobe not found next to ffmpeg. Set FFMPEG_PATH correctly.');
    }
}
