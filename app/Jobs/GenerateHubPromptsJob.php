<?php

namespace App\Jobs;

use App\Enums\HubIdeaStatus;
use App\Models\HubIdea;
use App\Services\OpenAiTextService;
use App\Support\HubDevelopmentPromptsParser;
use App\Support\HubPromptGenerationPrompt;
use App\Support\KlausImagePrompt;
use App\Support\KlausScriptBookends;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateHubPromptsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public HubIdea $hubIdea) {}

    public function handle(OpenAiTextService $openAi): void
    {
        $prompt = HubPromptGenerationPrompt::render($this->hubIdea->idea_text);

        if (! $prompt) {
            throw new \RuntimeException('Ideas Hub prompt generation requires idea text.');
        }

        $this->hubIdea->update(['status' => HubIdeaStatus::Draft, 'error_message' => null]);

        try {
            $result = $openAi->chat(
                'You write Klaus vom Amt short-form video scripts and scene image prompts. Tone: sarcastic, passive-aggressive, dark humor, deadpan, bureaucratic, mildly disappointed. Entertainment only.',
                $prompt,
            );

            $script = KlausScriptBookends::apply(trim($result['script'] ?? ''));
            $imagePrompt = trim($result['image_prompt'] ?? '');

            if ($script === '' || $imagePrompt === '') {
                throw new \RuntimeException('Content generation returned incomplete script or image prompt.');
            }

            $this->hubIdea->update([
                'script' => $script,
                'image_prompt' => KlausImagePrompt::buildFull($imagePrompt),
                'chatgpt_development_response' => HubDevelopmentPromptsParser::encode([
                    'script' => $script,
                    'image_prompt' => $imagePrompt,
                ]),
                'status' => HubIdeaStatus::ContentReady,
            ]);
        } catch (Throwable $exception) {
            $this->hubIdea->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
