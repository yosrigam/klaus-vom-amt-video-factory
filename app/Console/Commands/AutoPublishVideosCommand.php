<?php

namespace App\Console\Commands;

use App\Enums\VideoIdeaStatus;
use App\Models\VideoIdea;
use App\Services\VideoPublishService;
use Illuminate\Console\Command;

class AutoPublishVideosCommand extends Command
{
    protected $signature = 'klaus:auto-publish';

    protected $description = 'Pick the next video_ready idea and publish to active platforms';

    public function handle(VideoPublishService $publishService): int
    {
        $idea = VideoIdea::query()
            ->where('status', VideoIdeaStatus::VideoReady)
            ->oldest('id')
            ->first();

        if (! $idea) {
            $this->info('No video_ready ideas found.');

            return self::SUCCESS;
        }

        $publishService->ensureSocialPosts($idea);
        $publishService->dispatchPublishing($idea);

        $this->info("Publishing queued for video idea #{$idea->id}: {$idea->title}");

        return self::SUCCESS;
    }
}
