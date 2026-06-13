<?php

namespace App\Services;

use App\Contracts\PublishableVideo;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TikTokPublisherService
{
    public function publish(PublishableVideo $video, SocialPost $post, SocialAccount $account): array
    {
        $accessToken = $account->access_token ?: config('services.tiktok.access_token');

        if (! $accessToken) {
            throw new RuntimeException('TikTok access token missing.');
        }

        $videoPath = Storage::disk('local')->path((string) $video->publishVideoPath());
        $videoSize = filesize($videoPath);

        $init = Http::withToken($accessToken)
            ->post('https://open.tiktokapis.com/v2/post/publish/video/init/', [
                'post_info' => [
                    'title' => $video->publishTitle(),
                    'privacy_level' => config('services.tiktok.privacy_level', 'SELF_ONLY'),
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                    'brand_content_toggle' => false,
                    'brand_organic_toggle' => false,
                    'video_cover_timestamp_ms' => 1000,
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $videoSize,
                    'chunk_size' => $videoSize,
                    'total_chunk_count' => 1,
                ],
            ]);

        if (! $init->successful()) {
            throw new RuntimeException('TikTok init failed: '.$init->body());
        }

        $uploadUrl = $init->json('data.upload_url');
        $publishId = $init->json('data.publish_id');

        $upload = Http::withHeaders([
            'Content-Type' => 'video/mp4',
            'Content-Range' => "bytes 0-".($videoSize - 1)."/{$videoSize}",
        ])->withBody(file_get_contents($videoPath), 'video/mp4')
            ->put($uploadUrl);

        if (! $upload->successful()) {
            throw new RuntimeException('TikTok upload failed: '.$upload->body());
        }

        return [
            'platform_post_id' => $publishId,
            'platform_url' => null,
        ];
    }
}
