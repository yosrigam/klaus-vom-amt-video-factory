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

class GenerateIdeasJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 300;

    public function __construct(public string $contentPillar) {}

    public function handle(OpenAiTextService $openAi): void
    {
        $pillar = config("content_pillars.{$this->contentPillar}");

        if (! $pillar) {
            throw new \RuntimeException("Unknown content pillar: {$this->contentPillar}");
        }

        $template = PromptTemplate::activeFor(WorkflowStep::IdeaGeneration);

        if (! $template) {
            throw new \RuntimeException('No active idea generation prompt template.');
        }

        $run = $this->startWorkflowRun(null, WorkflowStep::IdeaGeneration, [
            'content_pillar' => $this->contentPillar,
        ]);

        try {
            $prompt = $template->render([
                'pillar_name' => $pillar['name'],
                'pillar_description' => $pillar['description'],
                'pillar_examples' => implode(', ', $pillar['examples']),
                'klaus_angle' => $pillar['klaus_angle'],
                'disclaimer' => config('klaus.disclaimer'),
            ]);

            $result = $openAi->chat(
                'You generate satirical short-form video ideas for Klaus vom Amt. Return JSON only.',
                $prompt,
            );

            $ideas = $result['ideas'] ?? [];

            if (count($ideas) !== 10) {
                throw new \RuntimeException('Expected exactly 10 ideas, got '.count($ideas).'.');
            }

            $created = [];

            foreach ($ideas as $idea) {
                $created[] = VideoIdea::query()->create([
                    'content_pillar' => $this->contentPillar,
                    'title' => $idea['title'],
                    'hook' => $idea['hook'],
                    'short_concept' => $idea['short_concept'],
                    'status' => VideoIdeaStatus::Draft,
                ]);
            }

            $this->completeWorkflowRun([
                'count' => count($created),
                'video_idea_ids' => collect($created)->pluck('id')->all(),
            ]);
        } catch (Throwable $exception) {
            $this->failWorkflowRun($exception);
            throw $exception;
        }
    }
}
