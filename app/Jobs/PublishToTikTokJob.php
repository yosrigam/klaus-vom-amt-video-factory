<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Enums\WorkflowStep;
use App\Jobs\Concerns\TracksWorkflowRun;
use App\Models\SocialPost;
use App\Services\TikTokPublisherService;
use App\Services\VideoPublishService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishToTikTokJob implements ShouldQueue
{
    use Queueable;
    use TracksWorkflowRun;

    public int $timeout = 600;

    public function __construct(public SocialPost $socialPost) {}

    public function handle(TikTokPublisherService $publisher, VideoPublishService $publishService): void
    {
        $idea = $this->socialPost->videoIdea;
        $account = $this->socialPost->socialAccount;
        $run = $this->startWorkflowRun($idea, WorkflowStep::Publishing, ['platform' => 'tiktok']);

        try {
            $this->socialPost->update(['status' => SocialPostStatus::Uploading, 'error_message' => null]);
            $result = $publisher->publish($idea, $this->socialPost, $account);

            $this->socialPost->update([
                'status' => SocialPostStatus::Published,
                'platform_post_id' => $result['platform_post_id'],
                'platform_url' => $result['platform_url'],
                'published_at' => now(),
            ]);

            $this->completeWorkflowRun($result);
            $publishService->markPublishedIfComplete($idea);
        } catch (Throwable $exception) {
            $this->socialPost->markFailed($exception->getMessage());
            $this->failWorkflowRun($exception, $idea);
            throw $exception;
        }
    }
}
