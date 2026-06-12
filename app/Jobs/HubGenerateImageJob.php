<?php

namespace App\Jobs;

use App\Enums\HubIdeaStatus;
use App\Models\HubIdea;
use App\Services\OpenAiImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class HubGenerateImageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public HubIdea $hubIdea) {}

    public function handle(OpenAiImageService $images): void
    {
        if (! $this->hubIdea->image_prompt) {
            throw new \RuntimeException('Image prompt is required.');
        }

        $this->hubIdea->update(['error_message' => null]);

        try {
            $path = $images->generateAndStore($this->hubIdea->image_prompt);

            $this->hubIdea->update([
                'image_path' => $path,
                'status' => HubIdeaStatus::ImageReady,
            ]);
        } catch (Throwable $exception) {
            $this->hubIdea->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
