<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use App\Services\TikTokPublisherService;
use App\Services\VideoPublishService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishToTikTokJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public SocialPost $socialPost) {}

    public function handle(TikTokPublisherService $publisher, VideoPublishService $publishService): void
    {
        $publishable = $this->socialPost->publishable();
        $account = $this->socialPost->socialAccount;

        try {
            $this->socialPost->update(['status' => SocialPostStatus::Uploading, 'error_message' => null]);
            $result = $publisher->publish($publishable, $this->socialPost, $account);

            $this->socialPost->update([
                'status' => SocialPostStatus::Published,
                'platform_post_id' => $result['platform_post_id'],
                'platform_url' => $result['platform_url'],
                'published_at' => now(),
            ]);

            if ($this->socialPost->video_idea_id !== null) {
                $publishService->markPublishedIfComplete($this->socialPost->videoIdea);
            }
        } catch (Throwable $exception) {
            $this->socialPost->markFailed($exception->getMessage());
            $this->socialPost->videoIdea?->markFailed($exception->getMessage());
            $this->socialPost->hubIdea?->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
