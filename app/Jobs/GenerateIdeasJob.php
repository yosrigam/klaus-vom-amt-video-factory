<?php

namespace App\Jobs;

use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\VideoIdea;
use App\Services\OpenAiTextService;
use App\Support\IdeaGenerationPrompt;
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

        $prompt = IdeaGenerationPrompt::render($this->contentPillar);

        if (! $prompt) {
            throw new \RuntimeException('No active idea generation prompt template.');
        }

        $run = $this->startWorkflowRun(null, WorkflowStep::IdeaGeneration, [
            'content_pillar' => $this->contentPillar,
        ]);

        try {
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
