<?php

namespace App\Services;

use App\Support\AudioProbe;
use App\Support\ScriptPhrases;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CaptionService
{
    public function __construct(
        protected WordHighlightCaptionRenderer $phraseRenderer,
    ) {}

    public function generateFromScript(string $script, ?string $audioPath = null, string $directory = 'klaus/captions'): string
    {
        $phraseTimings = $this->loadPhraseTimings($script, $audioPath);

        if ($phraseTimings === []) {
            throw new RuntimeException('Phrase timings are required for caption generation. Regenerate voice first.');
        }

        $runId = uniqid('captions_', true);
        $runDirectory = $directory.'/'.$runId;
        $disk = Storage::disk('local');
        $disk->makeDirectory($runDirectory);

        $segments = [];
        $maxBandHeight = 0;

        foreach ($phraseTimings as $index => $phraseTiming) {
            $imageName = sprintf('seg_%04d.png', $index + 1);
            $imagePath = $runDirectory.'/'.$imageName;
            $line = trim((string) ($phraseTiming['text'] ?? ''));

            if ($line === '') {
                continue;
            }

            $dimensions = $this->phraseRenderer->renderPhrase(
                $line,
                $disk->path($imagePath),
            );

            $maxBandHeight = max($maxBandHeight, $dimensions['height']);

            $segments[] = [
                'start' => round((float) $phraseTiming['start'], 3),
                'end' => round((float) $phraseTiming['end'], 3),
                'line' => $line,
                'image' => $imageName,
                'height' => $dimensions['height'],
                'overlay_y' => $this->overlayY($dimensions['height']),
            ];
        }

        $manifest = [
            'version' => 2,
            'style' => 'phrase',
            'width' => (int) config('klaus.video_width', 1080),
            'height' => (int) config('klaus.video_height', 1920),
            'overlay_y' => $this->overlayY($maxBandHeight),
            'segments' => $segments,
        ];

        $manifestPath = $runDirectory.'/manifest.json';
        $disk->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $manifestPath;
    }

    /**
     * @return array<int, array{text: string, start: float, end: float}>
     */
    protected function loadPhraseTimings(string $script, ?string $audioPath): array
    {
        if ($audioPath === null || ! Storage::disk('local')->exists($audioPath)) {
            return [];
        }

        $sidecarPath = AudioProbe::timingSidecarPath($audioPath);

        if (! Storage::disk('local')->exists($sidecarPath)) {
            return [];
        }

        $payload = json_decode((string) Storage::disk('local')->get($sidecarPath), true);

        if (! is_array($payload) || ! is_array($payload['phrases'] ?? null)) {
            return [];
        }

        $timedPhrases = $payload['phrases'];
        $scriptPhrases = ScriptPhrases::split($script);

        if (count($timedPhrases) !== count($scriptPhrases)) {
            throw new RuntimeException('Caption phrase count does not match voice phrase count. Regenerate voice.');
        }

        $normalized = [];

        foreach ($timedPhrases as $index => $phraseTiming) {
            $normalized[] = [
                'text' => (string) ($phraseTiming['text'] ?? $scriptPhrases[$index] ?? ''),
                'start' => (float) ($phraseTiming['start'] ?? 0.0),
                'end' => (float) ($phraseTiming['end'] ?? 0.0),
            ];
        }

        return $normalized;
    }

    protected function overlayY(int $bandHeight): int
    {
        $videoHeight = (int) config('klaus.video_height', 1920);
        $marginBottom = (int) config('klaus.captions.margin_bottom', 320);

        return max(0, $videoHeight - $marginBottom - $bandHeight);
    }
}
