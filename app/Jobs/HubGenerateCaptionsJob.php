<?php

namespace App\Jobs;

use App\Enums\HubIdeaStatus;
use App\Models\HubIdea;
use App\Services\CaptionService;
use App\Support\KlausScriptBookends;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HubGenerateCaptionsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public HubIdea $hubIdea) {}

    public function handle(CaptionService $captions): void
    {
        $rawScript = trim($this->hubIdea->script ?: '');

        if ($rawScript === '') {
            throw new \RuntimeException('Script is required for captions.');
        }

        if (! $this->hubIdea->audio_path || ! Storage::disk('local')->exists($this->hubIdea->audio_path)) {
            throw new \RuntimeException('Voice audio is required for captions. Generate voice first.');
        }

        $script = KlausScriptBookends::apply($rawScript);

        $this->hubIdea->update(['error_message' => null]);

        try {
            $path = $captions->generateFromScript($script, $this->hubIdea->audio_path);

            $this->hubIdea->update([
                'captions_path' => $path,
                'status' => HubIdeaStatus::CaptionsReady,
            ]);
        } catch (Throwable $exception) {
            $this->hubIdea->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
