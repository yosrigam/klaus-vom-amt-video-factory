<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\VideoIdea;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class YouTubePublisherService
{
    public function publish(VideoIdea $idea, SocialPost $post, SocialAccount $account): array
    {
        $accessToken = $this->resolveAccessToken($account);
        $videoPath = Storage::disk('local')->path($idea->video_path);

        if (! file_exists($videoPath)) {
            throw new RuntimeException('Video file not found for YouTube upload.');
        }

        $metadata = [
            'snippet' => [
                'title' => $idea->publish_title ?? $idea->title,
                'description' => $this->buildDescription($idea),
                'tags' => $idea->hashtags ?? [],
                'categoryId' => '23',
            ],
            'status' => [
                'privacyStatus' => 'public',
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $init = Http::withToken($accessToken)
            ->withQueryParameters(['uploadType' => 'resumable', 'part' => 'snippet,status'])
            ->post('https://www.googleapis.com/upload/youtube/v3/videos', $metadata);

        if (! $init->successful()) {
            throw new RuntimeException('YouTube upload init failed: '.$init->body());
        }

        $uploadUrl = $init->header('Location')[0] ?? null;

        if (! $uploadUrl) {
            throw new RuntimeException('YouTube upload URL missing.');
        }

        $upload = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'video/mp4'])
            ->withBody(file_get_contents($videoPath), 'video/mp4')
            ->put($uploadUrl);

        if (! $upload->successful()) {
            throw new RuntimeException('YouTube upload failed: '.$upload->body());
        }

        $videoId = $upload->json('id');

        return [
            'platform_post_id' => $videoId,
            'platform_url' => $videoId ? "https://youtube.com/shorts/{$videoId}" : null,
        ];
    }

    protected function resolveAccessToken(SocialAccount $account): string
    {
        if ($account->access_token) {
            return $account->access_token;
        }

        $refreshToken = $account->refresh_token ?: config('services.youtube.refresh_token');

        if (! $refreshToken) {
            throw new RuntimeException('YouTube refresh token missing.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('YouTube token refresh failed: '.$response->body());
        }

        $token = $response->json('access_token');
        $account->update([
            'access_token' => $token,
            'token_expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
        ]);

        return $token;
    }

    protected function buildDescription(VideoIdea $idea): string
    {
        $tags = collect($idea->hashtags ?? [])->map(fn ($tag) => '#'.ltrim($tag, '#'))->implode(' ');

        return trim(($idea->publish_description ?? $idea->short_concept)."\n\n".$tags."\n\n".config('klaus.disclaimer'));
    }
}
