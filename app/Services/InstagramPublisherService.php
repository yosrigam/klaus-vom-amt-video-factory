<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\VideoIdea;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramPublisherService
{
    public function publish(VideoIdea $idea, SocialPost $post, SocialAccount $account): array
    {
        $accessToken = $account->access_token ?: config('services.instagram.access_token');
        $igUserId = $account->metadata['ig_user_id'] ?? config('services.instagram.ig_user_id');
        $videoUrl = $this->publicVideoUrl($idea);

        if (! $accessToken || ! $igUserId) {
            throw new RuntimeException('Instagram credentials missing.');
        }

        $container = Http::post("https://graph.facebook.com/v21.0/{$igUserId}/media", [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $this->buildCaption($idea),
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

    protected function publicVideoUrl(VideoIdea $idea): string
    {
        $base = rtrim((string) (config('klaus.public_video_base_url') ?: config('app.url')), '/');

        return $base.route('klaus.media', ['path' => $idea->video_path], false);
    }

    protected function buildCaption(VideoIdea $idea): string
    {
        $tags = collect($idea->hashtags ?? [])->map(fn ($tag) => '#'.ltrim($tag, '#'))->implode(' ');

        return trim(($idea->publish_description ?? $idea->short_concept)."\n\n".$tags);
    }
}
