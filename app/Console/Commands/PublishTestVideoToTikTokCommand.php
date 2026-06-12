<?php

namespace App\Console\Commands;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Enums\VideoIdeaStatus;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\VideoIdea;
use App\Services\TikTokPublisherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublishTestVideoToTikTokCommand extends Command
{
    protected $signature = 'tiktok:publish-test {path=klaus/videos/klaus_test_silent.mp4}';

    protected $description = 'Publish a local test video to TikTok (sandbox: account must be private, SELF_ONLY)';

    public function handle(TikTokPublisherService $publisher): int
    {
        $videoPath = $this->argument('path');

        if (! Storage::disk('local')->exists($videoPath)) {
            $this->error("Video not found: storage/app/{$videoPath}");

            return self::FAILURE;
        }

        $account = SocialAccount::firstOrCreate(
            ['platform' => SocialPlatform::Tiktok, 'name' => 'Klaus vom Amt'],
            ['is_active' => true],
        );

        $idea = VideoIdea::create([
            'content_pillar' => 'test',
            'title' => 'Klaus Test Upload',
            'hook' => 'Test upload',
            'short_concept' => 'Automated TikTok API test upload.',
            'publish_title' => 'Klaus Test Upload #klausvomamt',
            'status' => VideoIdeaStatus::VideoReady,
            'video_path' => $videoPath,
        ]);

        $post = SocialPost::create([
            'video_idea_id' => $idea->id,
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Tiktok,
            'status' => SocialPostStatus::Uploading,
        ]);

        try {
            $result = $publisher->publish($idea, $post, $account);
            $post->update([
                'status' => SocialPostStatus::Published,
                'platform_post_id' => $result['platform_post_id'],
                'published_at' => now(),
            ]);
            $this->info('Published. publish_id: '.$result['platform_post_id']);
            $this->warn('Sandbox posts are SELF_ONLY until app audit. Check TikTok app → Profile → private videos.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $post->markFailed($exception->getMessage());
            $this->error($exception->getMessage());

            if (str_contains($exception->getMessage(), 'unaudited_client_can_only_post_to_private_accounts')) {
                $this->newLine();
                $this->line('Fix: TikTok app → Profile → Menu → Settings → Privacy → Private account ON');
                $this->line('Then run this command again.');
            }

            return self::FAILURE;
        }
    }
}
