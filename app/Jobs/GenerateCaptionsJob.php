<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\VideoIdea;
use App\Services\CaptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateCaptionsJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 120;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(CaptionService $captions): void
    {
        $script = $this->videoIdea->voice_text ?: $this->videoIdea->script;

        if (! $script) {
            throw new \RuntimeException('Script is required for captions.');
        }

        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::CaptionGeneration);

        try {
            $path = $captions->generateFromScript($script);

            $this->videoIdea->update([
                'captions_path' => $path,
                'status' => VideoIdeaStatus::CaptionsReady,
            ]);

            $this->completeWorkflowRun(['captions_path' => $path]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
