<?php

namespace App\Jobs;

use App\Enums\HubIdeaStatus;
use App\Enums\NarrationStyle;
use App\Models\HubIdea;
use App\Services\VoiceSynthesisService;
use App\Support\AwkwardNarrationFormatter;
use App\Support\KlausScriptBookends;
use App\Support\NarrationProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class HubGenerateVoiceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public HubIdea $hubIdea,
        public ?NarrationStyle $narrationStyle = null,
    ) {}

    public function handle(VoiceSynthesisService $tts): void
    {
        $style = $this->narrationStyle ?? NarrationStyle::fromConfig();
        $profile = NarrationProfile::for($style);

        $rawScript = $this->hubIdea->script ?: '';

        if ($rawScript === '') {
            throw new \RuntimeException('Voice script is required.');
        }

        $body = KlausScriptBookends::sanitizeBody($rawScript);

        if ($profile->applyFormatter) {
            $body = AwkwardNarrationFormatter::format($body);
        }

        $text = KlausScriptBookends::apply($body);

        $this->hubIdea->update(['script' => $text, 'error_message' => null]);

        try {
            $path = $tts->synthesize($text, style: $style);

            $this->hubIdea->update([
                'audio_path' => $path,
                'status' => HubIdeaStatus::VoiceReady,
            ]);
        } catch (Throwable $exception) {
            $this->hubIdea->markFailed($exception->getMessage());
            throw $exception;
        }
    }
}
