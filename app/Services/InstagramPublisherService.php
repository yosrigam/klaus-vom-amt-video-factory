<?php

namespace App\Services;

use App\Contracts\PublishableVideo;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Support\PublicVideoUrl;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramPublisherService
{
    public function publish(PublishableVideo $video, SocialPost $post, SocialAccount $account): array
    {
        $accessToken = $account->access_token ?: config('services.instagram.access_token');
        $igUserId = $account->metadata['ig_user_id'] ?? config('services.instagram.ig_user_id');
        $videoUrl = PublicVideoUrl::forPath((string) $video->publishVideoPath());

        if (! $accessToken || ! $igUserId) {
            throw new RuntimeException('Instagram credentials missing.');
        }

        $container = Http::post("https://graph.facebook.com/v21.0/{$igUserId}/media", [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $this->buildCaption($video),
            'access_token' => $accessToken,
        ]);

        if (! $container->successful()) {
            throw new RuntimeException('Instagram container failed: '.$container->body());
        }

        $creationId = $container->json('id');
        $this->waitForContainer($creationId, $accessToken);

        $publish = Http::post("https://graph.facebook.com/v21.0/{$igUserId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ]);

        if (! $publish->successful()) {
            throw new RuntimeException('Instagram publish failed: '.$publish->body());
        }

        $mediaId = $publish->json('id');

        return [
            'platform_post_id' => $mediaId,
            'platform_url' => $mediaId ? "https://www.instagram.com/reel/{$mediaId}/" : null,
        ];
    }

    protected function waitForContainer(string $creationId, string $accessToken): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $status = Http::get("https://graph.facebook.com/v21.0/{$creationId}", [
                'fields' => 'status_code',
                'access_token' => $accessToken,
            ]);

            $code = $status->json('status_code');

            if ($code === 'FINISHED') {
                return;
            }

            if ($code === 'ERROR') {
                throw new RuntimeException('Instagram media processing failed.');
            }

            sleep(3);
        }

        throw new RuntimeException('Instagram media processing timed out.');
    }

    protected function buildCaption(PublishableVideo $video): string
    {
        $tags = collect($video->publishHashtags())->map(fn ($tag) => '#'.ltrim($tag, '#'))->implode(' ');

        return trim($video->publishDescription()."\n\n".$tags);
    }
}
