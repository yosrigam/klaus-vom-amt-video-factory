<?php

namespace App\Models;

use App\Enums\WorkflowRunStatus;
use App\Enums\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRun extends Model
{
    protected $fillable = [
        'video_idea_id',
        'workflow_step',
        'status',
        'input',
        'output',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'workflow_step' => WorkflowStep::class,
            'status' => WorkflowRunStatus::class,
            'input' => 'array',
            'output' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function videoIdea(): BelongsTo
    {
        return $this->belongsTo(VideoIdea::class);
    }

    public function markCompleted(array $output = []): void
    {
        $this->update([
            'status' => WorkflowRunStatus::Completed,
            'output' => $output,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => WorkflowRunStatus::Failed,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }
}
