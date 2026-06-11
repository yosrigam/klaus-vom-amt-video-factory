<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\VideoIdea;
use App\Services\EdgeTtsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateVoiceJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 300;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(EdgeTtsService $tts): void
    {
        $text = $this->videoIdea->voice_text ?: $this->videoIdea->script;

        if (! $text) {
            throw new \RuntimeException('Voice text is required.');
        }

        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::VoiceGeneration);

        try {
            $path = $tts->synthesize($text);

            $this->videoIdea->update([
                'audio_path' => $path,
                'status' => VideoIdeaStatus::VoiceReady,
            ]);

            $this->completeWorkflowRun(['audio_path' => $path]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
