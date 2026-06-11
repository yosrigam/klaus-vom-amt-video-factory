<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class FfmpegVideoService
{
    public function composeVerticalVideo(string $imagePath, string $audioPath, string $captionsPath, string $directory = 'klaus/videos'): string
    {
        $disk = Storage::disk('local');
        $outputPath = $directory.'/'.uniqid('video_', true).'.mp4';
        $absoluteOutput = $disk->path($outputPath);
        $absoluteImage = $disk->path($imagePath);
        $absoluteAudio = $disk->path($audioPath);
        $absoluteCaptions = $disk->path($captionsPath);

        $disk->makeDirectory($directory);

        $width = config('klaus.video_width', 1080);
        $height = config('klaus.video_height', 1920);
        $escapedCaptions = $this->escapeSubtitlesPath($absoluteCaptions);

        $filter = implode(',', [
            "scale={$width}:{$height}:force_original_aspect_ratio=increase",
            "crop={$width}:{$height}",
            'zoompan=z=\'min(zoom+0.0015,1.15)\':d=1:x=\'iw/2-(iw/zoom/2)\':y=\'ih/2-(ih/zoom/2)\':s='.$width.'x'.$height,
            "subtitles={$escapedCaptions}:force_style='FontName=Arial,FontSize=28,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=3,Outline=2,Shadow=0,Alignment=2,MarginV=120,Bold=1'",
        ]);

        $process = new Process([
            config('klaus.ffmpeg_path'),
            '-y',
            '-loop', '1',
            '-i', $absoluteImage,
            '-i', $absoluteAudio,
            '-vf', $filter,
            '-c:v', 'libx264',
            '-tune', 'stillimage',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            '-movflags', '+faststart',
            $absoluteOutput,
        ]);

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($absoluteOutput)) {
            throw new RuntimeException('FFmpeg failed: '.$process->getErrorOutput());
        }

        return $outputPath;
    }

    protected function escapeSubtitlesPath(string $path): string
    {
        return str_replace(['\\', ':', ',', '[', ']'], ['\\\\', '\\:', '\\,', '\\[', '\\]'], $path);
    }
}
