<?php

namespace App\Jobs;

use App\Enums\HubIdeaStatus;
use App\Models\HubIdea;
use App\Services\FfmpegVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class HubGenerateVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public HubIdea $hubIdea) {}

    public function handle(FfmpegVideoService $ffmpeg): void
    {
        if (! $this->hubIdea->image_path || ! $this->hubIdea->audio_path || ! $this->hubIdea->captions_path) {
            throw new \RuntimeException('Image, audio, and captions are required for video generation.');
        }

        $this->hubIdea->update(['error_message' => null]);

        try {
            $path = $ffmpeg->composeVerticalVideo(
                $this->hubIdea->image_path,
                $this->hubIdea->audio_path,
                $this->hubIdea->captions_path,
                backgroundMusicPath: $this->hubIdea->background_music_path,
            );

            $this->hubIdea->update([
                'video_path' => $path,
                'status' => HubIdeaStatus::VideoReady,
            ]);
        } catch (Throwable $exception) {
            $this->hubIdea->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
