<?php

namespace App\Support;

use App\Jobs\GenerateCaptionsJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateImagePromptJob;
use App\Jobs\GenerateScriptJob;
use App\Jobs\GenerateVideoJob;
use App\Jobs\GenerateVoiceJob;
use App\Models\VideoIdea;

class VideoIdeaWorkflow
{
    public static function dispatchRetry(VideoIdea $idea): void
    {
        $idea->clearError();

        if (! $idea->script) {
            GenerateScriptJob::dispatch($idea);

            return;
        }

        if (! $idea->image_prompt) {
            GenerateImagePromptJob::dispatch($idea);

            return;
        }

        if (! $idea->image_path) {
            GenerateImageJob::dispatch($idea);

            return;
        }

        if (! $idea->audio_path) {
            GenerateVoiceJob::dispatch($idea);

            return;
        }

        if (! $idea->captions_path) {
            GenerateCaptionsJob::dispatch($idea);

            return;
        }

        if (! $idea->video_path) {
            GenerateVideoJob::dispatch($idea);
        }
    }
}
