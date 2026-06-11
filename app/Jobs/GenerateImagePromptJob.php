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

class GenerateImagePromptJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 180;

    public function __construct(public VideoIdea $videoIdea) {}

    public function handle(OpenAiTextService $openAi): void
    {
        if (! $this->videoIdea->script) {
            throw new \RuntimeException('Script is required before image prompt generation.');
        }

        $template = PromptTemplate::activeFor(WorkflowStep::ImagePromptGeneration);

        if (! $template) {
            throw new \RuntimeException('No active image prompt generation template.');
        }

        $this->videoIdea->update(['status' => VideoIdeaStatus::Wip, 'error_message' => null]);
        $run = $this->startWorkflowRun($this->videoIdea, WorkflowStep::ImagePromptGeneration);

        try {
            $prompt = $template->render([
                'idea' => $this->videoIdea->title.': '.$this->videoIdea->short_concept,
                'script' => $this->videoIdea->script,
                'character_lock' => config('klaus.character_lock'),
                'visual_style' => config('klaus.visual_style'),
            ]);

            $result = $openAi->chat(
                'You create image prompts for Klaus vom Amt vertical social videos. Return JSON only.',
                $prompt,
            );

            $imagePrompt = trim($result['image_prompt'] ?? '');

            if ($imagePrompt === '') {
                throw new \RuntimeException('Image prompt generation returned empty prompt.');
            }

            $this->videoIdea->update([
                'image_prompt' => $imagePrompt,
                'status' => VideoIdeaStatus::ImagePromptReady,
            ]);

            $this->completeWorkflowRun(['image_prompt_length' => strlen($imagePrompt)]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception, $this->videoIdea);
            throw $exception;
        }
    }
}
