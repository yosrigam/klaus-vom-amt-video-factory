<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\PromptTemplate;
use App\Models\VideoIdea;
use App\Services\OpenAiTextService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateScriptJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 300;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(OpenAiTextService $openAi): void
    {
        $template = PromptTemplate::activeFor(WorkflowStep::ScriptGeneration);

        if (! $template) {
            throw new \RuntimeException('No active script generation prompt template.');
        }

        $pillar = config("content_pillars.{$this->videoIdea->content_pillar}", []);
        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::ScriptGeneration);

        try {
            $prompt = $template->render([
                'title' => $this->videoIdea->title,
                'hook' => $this->videoIdea->hook,
                'short_concept' => $this->videoIdea->short_concept,
                'pillar_name' => $pillar['name'] ?? $this->videoIdea->content_pillar,
                'klaus_angle' => $pillar['klaus_angle'] ?? '',
                'disclaimer' => config('klaus.disclaimer'),
            ]);

            $result = $openAi->chat(
                'You write Klaus vom Amt scripts. Tone: sarcastic, passive-aggressive, dark humor, deadpan, bureaucratic, mildly disappointed. Entertainment only.',
                $prompt,
            );

            $script = trim($result['script'] ?? '');
            $voiceText = trim($result['voice_text'] ?? $script);

            if ($script === '') {
                throw new \RuntimeException('Script generation returned empty script.');
            }

            $this->videoIdea->update([
                'script' => $script,
                'voice_text' => $voiceText,
                'publish_title' => $result['publish_title'] ?? $this->videoIdea->title,
                'publish_description' => $result['publish_description'] ?? $this->videoIdea->short_concept,
                'hashtags' => $result['hashtags'] ?? ['KlausVomAmt', 'Germany', 'Deutschland'],
                'status' => VideoIdeaStatus::ScriptReady,
            ]);

            $this->completeWorkflowRun(['script_length' => strlen($script)]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
