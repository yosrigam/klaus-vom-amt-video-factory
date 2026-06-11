<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\VideoIdea;
use App\Services\OpenAiImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateImageJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 300;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(OpenAiImageService $images): void
    {
        if (! $this->videoIdea->image_prompt) {
            throw new \RuntimeException('Image prompt is required.');
        }

        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::ImageGeneration);

        try {
            $path = $images->generateAndStore($this->videoIdea->image_prompt);

            $this->videoIdea->update([
                'image_path' => $path,
                'status' => VideoIdeaStatus::ImageReady,
            ]);

            $this->completeWorkflowRun(['image_path' => $path]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
