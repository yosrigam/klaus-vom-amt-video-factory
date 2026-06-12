<?php

namespace Tests\Feature;

use App\Enums\HubIdeaStatus;
use App\Jobs\HubGenerateCaptionsJob;
use App\Jobs\HubGenerateVideoJob;
use App\Jobs\HubGenerateVoiceJob;
use App\Models\HubIdea;
use App\Support\HubIdeaBulkParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HubIdeaProductionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_hub_ideas_from_bulk_text(): void
    {
        $ideas = HubIdeaBulkParser::parse("Idea alpha\n\nIdea beta");

        foreach ($ideas as $idea) {
            HubIdea::query()->create([
                'idea_text' => $idea['idea_text'],
                'title' => $idea['title'],
                'status' => HubIdeaStatus::Draft,
            ]);
        }

        $this->assertDatabaseCount('hub_ideas', 2);
        $this->assertDatabaseHas('hub_ideas', ['idea_text' => 'Idea alpha']);
    }

    #[Test]
    public function it_produces_mp4_from_script_and_uploaded_image(): void
    {
        if (! $this->productionBinariesAvailable()) {
            $this->markTestSkipped('edge-tts or ffmpeg is not available in this environment.');
        }

        $sourceImage = base_path('storage/app/private/klaus/images/klaus_6a2c0754bf87d7.56778000.png');

        if (! file_exists($sourceImage)) {
            $this->markTestSkipped('No sample Klaus image available for production test.');
        }

        Storage::disk('local')->makeDirectory('klaus/images');
        $imagePath = 'klaus/images/hub_test_'.uniqid('', true).'.png';
        Storage::disk('local')->put($imagePath, file_get_contents($sourceImage));

        $hubIdea = HubIdea::query()->create([
            'idea_text' => 'Pfand ritual initiation',
            'title' => 'Pfand ritual initiation',
            'script' => 'Klaus vom Amt here. Unfortunately, Germany has reviewed your bottle deposit behavior. You placed an empty bottle on the table without sorting it first. This is now a formal Pfand procedure.',
            'image_path' => $imagePath,
            'status' => HubIdeaStatus::ImageReady,
        ]);

        HubGenerateVoiceJob::dispatchSync($hubIdea->fresh());
        HubGenerateCaptionsJob::dispatchSync($hubIdea->fresh());
        HubGenerateVideoJob::dispatchSync($hubIdea->fresh());

        $hubIdea->refresh();

        $this->assertNotNull($hubIdea->audio_path);
        $this->assertNotNull($hubIdea->captions_path);
        $this->assertNotNull($hubIdea->video_path);
        $this->assertSame(HubIdeaStatus::VideoReady, $hubIdea->status);
        $this->assertTrue(Storage::disk('local')->exists($hubIdea->video_path));
    }

    private function productionBinariesAvailable(): bool
    {
        $ffmpeg = config('klaus.ffmpeg_path', 'ffmpeg');
        $edgeTts = config('klaus.edge_tts_path', 'edge-tts');

        return $this->commandExists($ffmpeg) && $this->commandExists($edgeTts);
    }

    private function commandExists(string $command): bool
    {
        $process = proc_open(
            'command -v '.escapeshellarg($command),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (! is_resource($process)) {
            return false;
        }

        $exitCode = proc_close($process);

        return $exitCode === 0;
    }
}
