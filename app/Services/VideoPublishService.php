<?php

namespace App\Services;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Enums\VideoIdeaStatus;
use App\Jobs\PublishToInstagramJob;
use App\Jobs\PublishToTikTokJob;
use App\Jobs\PublishToYouTubeJob;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\VideoIdea;

class VideoPublishService
{
    public function ensureSocialPosts(VideoIdea $idea): void
    {
        $accounts = SocialAccount::query()->where('is_active', true)->get();

        foreach ($accounts as $account) {
            SocialPost::query()->firstOrCreate(
                [
                    'video_idea_id' => $idea->id,
                    'social_account_id' => $account->id,
                ],
                [
                    'platform' => $account->platform,
                    'status' => SocialPostStatus::Scheduled,
                    'scheduled_at' => $idea->scheduled_at ?? now(),
                ],
            );
        }
    }

    public function dispatchPublishing(VideoIdea $idea): void
    {
        $this->ensureSocialPosts($idea);

        $idea->update(['status' => VideoIdeaStatus::Scheduled]);

        $posts = $idea->socialPosts()->whereIn('status', [
            SocialPostStatus::Pending,
            SocialPostStatus::Scheduled,
            SocialPostStatus::Failed,
        ])->get();

        foreach ($posts as $post) {
            match ($post->platform) {
                SocialPlatform::Youtube => PublishToYouTubeJob::dispatch($post),
                SocialPlatform::Instagram => PublishToInstagramJob::dispatch($post),
                SocialPlatform::Tiktok => PublishToTikTokJob::dispatch($post),
            };
        }
    }

    public function markPublishedIfComplete(VideoIdea $idea): void
    {
        $pending = $idea->socialPosts()
            ->whereNotIn('status', [SocialPostStatus::Published, SocialPostStatus::Failed])
            ->exists();

        if (! $pending) {
            $idea->update(['status' => VideoIdeaStatus::Published]);
        }
    }
}
