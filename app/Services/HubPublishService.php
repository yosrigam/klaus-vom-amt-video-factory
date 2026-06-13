<?php

namespace App\Services;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\PublishToInstagramJob;
use App\Jobs\PublishToTikTokJob;
use App\Jobs\PublishToYouTubeJob;
use App\Models\HubIdea;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HubPublishService
{
    public function dispatch(HubIdea $hubIdea, SocialPlatform $platform): SocialPost
    {
        if (blank($hubIdea->video_path) || ! Storage::disk('local')->exists($hubIdea->video_path)) {
            throw new RuntimeException('Produce a video before publishing.');
        }

        if ($platform === SocialPlatform::Instagram && ! $this->instagramReachable()) {
            throw new RuntimeException(
                'Instagram requires a public video URL. Set APP_PUBLIC_VIDEO_BASE_URL to an HTTPS tunnel (e.g. ngrok) reachable from Meta\'s servers.',
            );
        }

        $account = SocialAccount::resolveForPlatform($platform);

        $post = SocialPost::query()->updateOrCreate(
            [
                'hub_idea_id' => $hubIdea->id,
                'social_account_id' => $account->id,
            ],
            [
                'platform' => $platform,
                'status' => SocialPostStatus::Scheduled,
                'scheduled_at' => now(),
                'error_message' => null,
            ],
        );

        match ($platform) {
            SocialPlatform::Youtube => PublishToYouTubeJob::dispatch($post),
            SocialPlatform::Instagram => PublishToInstagramJob::dispatch($post),
            SocialPlatform::Tiktok => PublishToTikTokJob::dispatch($post),
        };

        return $post;
    }

    protected function instagramReachable(): bool
    {
        $base = (string) (config('klaus.public_video_base_url') ?: config('app.url'));

        return str_starts_with($base, 'https://')
            && ! str_contains($base, 'localhost')
            && ! str_contains($base, '127.0.0.1');
    }
}
