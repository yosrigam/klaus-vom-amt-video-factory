<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use App\Services\VideoPublishService;
use App\Services\YouTubePublisherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishToYouTubeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public SocialPost $socialPost) {}

    public function handle(YouTubePublisherService $publisher, VideoPublishService $publishService): void
    {
        $idea = $this->socialPost->videoIdea;
        $account = $this->socialPost->socialAccount;

        try {
            $this->socialPost->update(['status' => SocialPostStatus::Uploading, 'error_message' => null]);
            $result = $publisher->publish($idea, $this->socialPost, $account);

            $this->socialPost->update([
                'status' => SocialPostStatus::Published,
                'platform_post_id' => $result['platform_post_id'],
                'platform_url' => $result['platform_url'],
                'published_at' => now(),
            ]);

            $publishService->markPublishedIfComplete($idea);
        } catch (Throwable $exception) {
            $this->socialPost->markFailed($exception->getMessage());
            $idea?->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
