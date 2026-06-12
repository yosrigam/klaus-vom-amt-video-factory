<?php

namespace App\Services;

use App\Support\BackgroundMusic;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class FfmpegVideoService
{
    public function composeVerticalVideo(
        string $imagePath,
        string $audioPath,
        string $captionsPath,
        string $directory = 'klaus/videos',
        ?string $backgroundMusicPath = null,
    ): string {
        $disk = Storage::disk('local');
        $outputPath = $directory.'/'.uniqid('video_', true).'.mp4';
        $absoluteOutput = $disk->path($outputPath);
        $absoluteImage = $disk->path($imagePath);
        $absoluteAudio = $this->resolveMixedAudioPath($disk->path($audioPath), $backgroundMusicPath);
        $absoluteCaptions = $disk->path($captionsPath);

        $disk->makeDirectory($directory);

        $manifest = json_decode((string) file_get_contents($absoluteCaptions), true);

        $style = $manifest['style'] ?? null;

        if (! is_array($manifest) || ! in_array($style, ['phrase', 'word_highlight'], true)) {
            throw new RuntimeException('Invalid caption manifest.');
        }

        $segments = $manifest['segments'] ?? [];
        $width = (int) ($manifest['width'] ?? config('klaus.video_width', 1080));
        $height = (int) ($manifest['height'] ?? config('klaus.video_height', 1920));
        $overlayY = (int) ($manifest['overlay_y'] ?? ($height - 400));
        $manifestDir = dirname($absoluteCaptions);

        $baseFilter = implode(',', [
            "scale={$width}:{$height}:force_original_aspect_ratio=increase",
            "crop={$width}:{$height}",
            'zoompan=z=\'min(zoom+0.0015,1.15)\':d=1:x=\'iw/2-(iw/zoom/2)\':y=\'ih/2-(ih/zoom/2)\':s='.$width.'x'.$height,
        ]);

        if ($segments === []) {
            $this->runFfmpeg([
                config('klaus.ffmpeg_path'),
                '-y',
                '-loop', '1',
                '-i', $absoluteImage,
                '-i', $absoluteAudio,
                '-vf', $baseFilter,
                '-c:v', 'libx264',
                '-tune', 'stillimage',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-b:a', '192k',
                '-shortest',
                '-movflags', '+faststart',
                $absoluteOutput,
            ]);

            return $outputPath;
        }

        $command = [
            config('klaus.ffmpeg_path'),
            '-y',
            '-loop', '1',
            '-i', $absoluteImage,
            '-i', $absoluteAudio,
        ];

        foreach ($segments as $segment) {
            $command[] = '-i';
            $command[] = $manifestDir.'/'.$segment['image'];
        }

        $currentLabel = 'v0';
        $filterParts = ["[0:v]{$baseFilter}[{$currentLabel}]"];

        foreach ($segments as $index => $segment) {
            $inputIndex = $index + 2;
            $nextLabel = 'v'.($index + 1);
            $start = $this->formatFilterNumber((float) $segment['start']);
            $end = $this->formatFilterNumber((float) $segment['end']);
            $enable = "between(t\\,{$start}\\,{$end})";

            $segmentOverlayY = (int) ($segment['overlay_y'] ?? $overlayY);
            $filterParts[] = "[{$currentLabel}][{$inputIndex}:v]overlay=0:{$segmentOverlayY}:enable='{$enable}'[{$nextLabel}]";
            $currentLabel = $nextLabel;
        }

        $this->runFfmpeg(array_merge($command, [
            '-filter_complex', implode(';', $filterParts),
            '-map', "[{$currentLabel}]",
            '-map', '1:a',
            '-c:v', 'libx264',
            '-tune', 'stillimage',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            '-movflags', '+faststart',
            $absoluteOutput,
        ]));

        return $outputPath;
    }

    protected function resolveMixedAudioPath(string $narrationAbsolutePath, ?string $backgroundMusicPath = null): string
    {
        $musicPath = BackgroundMusic::resolve($backgroundMusicPath);

        if ($musicPath === null) {
            return $narrationAbsolutePath;
        }

        $musicVolume = max(0.0, min(1.0, (float) config('klaus.background_music_volume', 0.10)));
        $narrationVolume = max(0.1, min(3.0, (float) config('klaus.narration_volume', 1.35)));

        $mixedPath = Storage::disk('local')->path(
            'klaus/tmp/'.uniqid('mixed_audio_', true).'.mp3',
        );

        Storage::disk('local')->makeDirectory('klaus/tmp');

        $filter = sprintf(
            '[0:a]volume=%s[voice];[1:a]volume=%s[bg];[voice][bg]amix=inputs=2:duration=first:dropout_transition=2:normalize=0[aout]',
            $this->formatFilterNumber($narrationVolume),
            $this->formatFilterNumber($musicVolume),
        );

        $this->runFfmpeg([
            config('klaus.ffmpeg_path'),
            '-y',
            '-i', $narrationAbsolutePath,
            '-stream_loop', '-1',
            '-i', $musicPath,
            '-filter_complex', $filter,
            '-map', '[aout]',
            '-c:a', 'libmp3lame',
            '-q:a', '4',
            '-shortest',
            $mixedPath,
        ]);

        return $mixedPath;
    }

    /**
     * @param  array<int, string>  $command
     */
    protected function runFfmpeg(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        $outputPath = $command[array_key_last($command)];

        if (! $process->isSuccessful() || ! file_exists($outputPath)) {
            throw new RuntimeException('FFmpeg failed: '.$process->getErrorOutput());
        }
    }

    protected function formatFilterNumber(float $value): string
    {
        $formatted = rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
