<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\VideoIdea;
use App\Services\FfmpegVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateVideoJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 600;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(FfmpegVideoService $ffmpeg): void
    {
        if (! $this->videoIdea->image_path || ! $this->videoIdea->audio_path || ! $this->videoIdea->captions_path) {
            throw new \RuntimeException('Image, audio, and captions are required for video generation.');
        }

        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::VideoGeneration);

        try {
            $path = $ffmpeg->composeVerticalVideo(
                $this->videoIdea->image_path,
                $this->videoIdea->audio_path,
                $this->videoIdea->captions_path,
            );

            $this->videoIdea->update([
                'video_path' => $path,
                'status' => VideoIdeaStatus::VideoReady,
            ]);

            $this->completeWorkflowRun(['video_path' => $path]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
