<?php

namespace App\Jobs\Concerns;

use App\Enums\WorkflowRunStatus;
use App\Enums\WorkflowStep;
use App\Models\VideoIdea;
use App\Models\WorkflowRun;
use Throwable;

trait TracksWorkflowRun
{
    protected ?WorkflowRun $workflowRun = null;

    protected function startWorkflowRun(?VideoIdea $videoIdea, WorkflowStep $step, array $input = []): WorkflowRun
    {
        return $this->workflowRun = WorkflowRun::query()->create([
            'video_idea_id' => $videoIdea?->id,
            'workflow_step' => $step,
            'status' => WorkflowRunStatus::Running,
            'input' => $input,
            'started_at' => now(),
        ]);
    }

    protected function completeWorkflowRun(array $output = []): void
    {
        $this->workflowRun?->markCompleted($output);
    }

    protected function failWorkflowRun(Throwable $exception, ?VideoIdea $videoIdea = null): void
    {
        $message = $exception->getMessage();
        $this->workflowRun?->markFailed($message);
        $videoIdea?->markFailed($message);
    }
}
