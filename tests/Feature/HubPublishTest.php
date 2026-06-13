<?php

namespace Tests\Feature;

use App\Enums\HubIdeaStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\PublishToYouTubeJob;
use App\Models\HubIdea;
use App\Services\HubPublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubPublishTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_queues_youtube_publish_for_hub_ideas(): void
    {
        Bus::fake([PublishToYouTubeJob::class]);

        config([
            'services.youtube.refresh_token' => 'refresh-token',
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('klaus/videos/test.mp4', 'fake-video');

        $hubIdea = HubIdea::query()->create([
            'idea_text' => 'Autobahn expectations',
            'title' => 'Autobahn',
            'script' => 'Klaus vom Amt hier.',
            'video_path' => 'klaus/videos/test.mp4',
            'status' => HubIdeaStatus::VideoReady,
        ]);

        $post = app(HubPublishService::class)->dispatch($hubIdea, SocialPlatform::Youtube);

        $this->assertSame(SocialPostStatus::Scheduled, $post->status);
        $this->assertSame($hubIdea->id, $post->hub_idea_id);
        Bus::assertDispatched(PublishToYouTubeJob::class);
    }
}
