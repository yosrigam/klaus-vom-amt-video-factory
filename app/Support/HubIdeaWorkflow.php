<?php

namespace App\Support;

use App\Jobs\GenerateHubPromptsJob;
use App\Jobs\HubGenerateCaptionsJob;
use App\Jobs\HubGenerateImageJob;
use App\Jobs\HubGenerateVideoJob;
use App\Jobs\HubGenerateVoiceJob;
use App\Models\HubIdea;
use Illuminate\Support\Facades\Bus;

class HubIdeaWorkflow
{
    public static function dispatchRetry(HubIdea $hubIdea): void
    {
        if (! $hubIdea->script || ! $hubIdea->image_prompt) {
            GenerateHubPromptsJob::dispatch($hubIdea);

            return;
        }

        if (! $hubIdea->image_path && $hubIdea->image_prompt) {
            HubGenerateImageJob::dispatch($hubIdea);

            return;
        }

        if (! $hubIdea->audio_path && $hubIdea->script) {
            HubGenerateVoiceJob::dispatch($hubIdea);

            return;
        }

        if (! $hubIdea->captions_path && $hubIdea->script && $hubIdea->audio_path) {
            HubGenerateCaptionsJob::dispatch($hubIdea);

            return;
        }

        if (! $hubIdea->video_path && $hubIdea->image_path && $hubIdea->audio_path && $hubIdea->captions_path) {
            HubGenerateVideoJob::dispatch($hubIdea);
        }
    }

    public static function dispatchProduceAll(HubIdea $hubIdea): void
    {
        if (! $hubIdea->script) {
            throw new \RuntimeException('Script is required before producing video.');
        }

        if (! $hubIdea->image_path) {
            throw new \RuntimeException('Image is required before producing video.');
        }

        Bus::chain([
            new HubGenerateVoiceJob($hubIdea),
            new HubGenerateCaptionsJob($hubIdea),
            new HubGenerateVideoJob($hubIdea),
        ])->dispatch();
    }
}
