<?php

namespace App\Services\Concerns;

use App\Enums\NarrationStyle;
use App\Support\AudioProbe;
use App\Support\KlausDeliveryClassifier;
use App\Support\NarrationProfile;
use App\Support\NarrationScriptParser;
use App\Support\NarrationSegment;
use App\Support\PhraseDelivery;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

trait SynthesizesPhrasesToAudio
{
    protected ?NarrationProfile $narrationProfile = null;

    protected ?PhraseDelivery $currentPhraseDelivery = null;

    public function synthesize(
        string $text,
        string $directory = 'klaus/audio',
        NarrationStyle $style = NarrationStyle::Default,
    ): string {
        $this->narrationProfile = NarrationProfile::for($style);

        $path = $directory.'/'.uniqid('voice_', true).'.mp3';
        $absolutePath = Storage::disk('local')->path($path);

        Storage::disk('local')->makeDirectory($directory);

        if ((bool) config('klaus.edge_tts_chunk_sentences', true)) {
            $this->synthesizeChunked($text, $absolutePath);
        } else {
            $this->synthesizePhraseAudio($text, $absolutePath, 0, 1, $text);
            $this->writeTimingSidecar($path, [
                [
                    'text' => trim($text),
                    'start' => 0.0,
                    'end' => AudioProbe::durationSeconds($absolutePath) ?? 0.0,
                ],
            ]);
        }

        if ((bool) config('klaus.edge_tts_loudnorm_enabled', true)) {
            $this->normalizeLoudness($absolutePath);
        }

        if (! file_exists($absolutePath)) {
            throw new RuntimeException('Voice synthesis did not produce an audio file.');
        }

        return $path;
    }

    abstract protected function synthesizePhraseAudio(
        string $text,
        string $absoluteOutputPath,
        int $phraseIndex,
        int $phraseCount,
        string $phrase,
    ): void;

    protected function synthesizeChunked(string $text, string $absoluteOutputPath): void
    {
        $profile = $this->narrationProfile ?? NarrationProfile::for(NarrationStyle::Default);
        $segments = KlausDeliveryClassifier::annotate(NarrationScriptParser::parse($text, $profile));

        if ($segments === []) {
            throw new RuntimeException('Voice text is empty.');
        }

        $speechSegments = array_values(array_filter(
            $segments,
            static fn (NarrationSegment $segment) => $segment->isSpeech(),
        ));

        if (count($speechSegments) === 1 && count($segments) === 1) {
            $phrase = $speechSegments[0]->text;
            $voice = $this->voiceNameForPhrase(0, 1, $phrase);
            $this->currentPhraseDelivery = PhraseDelivery::fromSegment($speechSegments[0], $voice);
            $this->synthesizePhraseAudio($phrase, $absoluteOutputPath, 0, 1, $phrase);
            $this->trimTrailingSilence($absoluteOutputPath);
            $duration = AudioProbe::durationSeconds($absoluteOutputPath) ?? 0.0;
            $this->writeTimingSidecar(
                $this->relativePathFromAbsolute($absoluteOutputPath),
                [$this->phraseTimingEntry($speechSegments[0], $voice, 0.0, $duration)],
            );

            return;
        }

        $tmpDir = 'klaus/tmp/'.uniqid('voice_chunks_', true);
        $disk = Storage::disk('local');
        $disk->makeDirectory($tmpDir);

        $concatEntries = [];
        $phraseTimings = [];
        $timelineCursor = 0.0;
        $speechCount = count($speechSegments);
        $speechIndex = 0;

        try {
            foreach ($segments as $segmentIndex => $segment) {
                if ($segment->isPause()) {
                    $pausePath = $disk->path($tmpDir.'/marker_pause_'.$segmentIndex.'.mp3');
                    $this->generateSilence($segment->pauseSeconds, $pausePath);
                    $concatEntries[] = $this->concatFileEntry($pausePath);
                    $timelineCursor += $segment->pauseSeconds;

                    continue;
                }

                $previousSegment = $segments[$segmentIndex - 1] ?? null;
                $gapBefore = $this->gapBeforeSpeech($segment, $previousSegment);

                if ($gapBefore > 0) {
                    $pausePath = $disk->path($tmpDir.'/pre_pause_'.$segmentIndex.'.mp3');
                    $this->generateSilence($gapBefore, $pausePath);
                    $concatEntries[] = $this->concatFileEntry($pausePath);
                    $timelineCursor += $gapBefore;
                }

                $voice = $this->voiceNameForPhrase($speechIndex, $speechCount, $segment->text);

                $chunkPath = $disk->path($tmpDir.'/chunk_'.$segmentIndex.'.mp3');
                $this->currentPhraseDelivery = PhraseDelivery::fromSegment($segment, $voice);
                $this->synthesizePhraseAudio(
                    $segment->text,
                    $chunkPath,
                    $speechIndex,
                    $speechCount,
                    $segment->text,
                );
                $this->trimTrailingSilence($chunkPath);
                $chunkDuration = AudioProbe::durationSeconds($chunkPath) ?? 0.0;
                $phraseStart = $timelineCursor;

                $phraseTimings[] = $this->phraseTimingEntry(
                    $segment,
                    $voice,
                    $phraseStart,
                    $phraseStart + $chunkDuration,
                );

                $timelineCursor += $chunkDuration;
                $concatEntries[] = $this->concatFileEntry($chunkPath);
                $speechIndex++;
            }

            $listPath = $disk->path($tmpDir.'/concat.txt');
            file_put_contents($listPath, implode("\n", $concatEntries)."\n");

            $process = new Process([
                config('klaus.ffmpeg_path'),
                '-y',
                '-f', 'concat',
                '-safe', '0',
                '-i', $listPath,
                '-c', 'copy',
                $absoluteOutputPath,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('FFmpeg voice concat failed: '.$process->getErrorOutput());
            }

            $this->writeTimingSidecar($this->relativePathFromAbsolute($absoluteOutputPath), $phraseTimings);
        } finally {
            $disk->deleteDirectory($tmpDir);
        }
    }

    /**
     * One silence gap between phrases — never stack pause_after on phrase N with pause_before on N+1.
     */
    protected function gapBeforeSpeech(NarrationSegment $segment, ?NarrationSegment $previousSegment): float
    {
        if ($previousSegment === null) {
            return 0.0;
        }

        if ($previousSegment->isPause()) {
            return $segment->pauseBeforeSeconds;
        }

        if (! $previousSegment->isSpeech()) {
            return $segment->pauseBeforeSeconds;
        }

        return max($previousSegment->pauseAfterSeconds, $segment->pauseBeforeSeconds);
    }

    protected function voiceNameForPhrase(int $phraseIndex, int $phraseCount, string $phrase): string
    {
        if (method_exists($this, 'voiceForPhrase')) {
            return $this->voiceForPhrase($phraseIndex, $phraseCount, $phrase);
        }

        return (string) config('klaus.edge_tts_voice', config('klaus.voice_profile.voice', 'en-GB-ThomasNeural'));
    }

    /**
     * @return array{
     *     phrase: string,
     *     text: string,
     *     style: string,
     *     voice: string,
     *     rate: string,
     *     pitch: string,
     *     volume: string,
     *     pause_before: float,
     *     pause_after: float,
     *     start: float,
     *     end: float,
     *     starts_at: float,
     *     ends_at: float,
     * }
     */
    protected function phraseTimingEntry(
        NarrationSegment $segment,
        string $voice,
        float $start,
        float $end,
    ): array {
        $delivery = PhraseDelivery::fromSegment($segment, $voice);

        return [
            'phrase' => $segment->text,
            'text' => $segment->text,
            'style' => $delivery->style->value,
            'voice' => $voice,
            'rate' => $delivery->rate,
            'pitch' => $delivery->pitch,
            'volume' => $delivery->volume,
            'pause_before' => round($delivery->pauseBeforeSeconds, 3),
            'pause_after' => round($delivery->pauseAfterSeconds, 3),
            'start' => round($start, 3),
            'end' => round($end, 3),
            'starts_at' => round($start, 3),
            'ends_at' => round($end, 3),
        ];
    }

    protected function normalizeLoudness(string $absoluteOutputPath): void
    {
        $settings = config('klaus.edge_tts_loudnorm', []);
        $integrated = (float) ($settings['I'] ?? -16);
        $truePeak = (float) ($settings['TP'] ?? -1.5);
        $lra = (float) ($settings['LRA'] ?? 8);

        $normalizedPath = $absoluteOutputPath.'.norm.mp3';

        $process = new Process([
            config('klaus.ffmpeg_path'),
            '-y',
            '-i', $absoluteOutputPath,
            '-af', "loudnorm=I={$integrated}:TP={$truePeak}:LRA={$lra}",
            $normalizedPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('FFmpeg loudnorm failed: '.$process->getErrorOutput());
        }

        if (! rename($normalizedPath, $absoluteOutputPath)) {
            throw new RuntimeException('Failed to replace narration file after loudnorm.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $phrases
     */
    protected function writeTimingSidecar(string $audioRelativePath, array $phrases): void
    {
        $sidecarPath = AudioProbe::timingSidecarPath($audioRelativePath);

        Storage::disk('local')->put($sidecarPath, json_encode([
            'version' => 2,
            'phrases' => $phrases,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function relativePathFromAbsolute(string $absolutePath): string
    {
        $root = Storage::disk('local')->path('');

        if (str_starts_with($absolutePath, $root)) {
            return ltrim(substr($absolutePath, strlen($root)), '/');
        }

        return basename($absolutePath);
    }

    protected function trimTrailingSilence(string $absoluteOutputPath): void
    {
        if (! (bool) config('klaus.edge_tts_trim_trailing_silence', true)) {
            return;
        }

        $settings = config('klaus.edge_tts_trim_silence', []);
        $threshold = (int) ($settings['stop_threshold_db'] ?? -45);
        $duration = (float) ($settings['stop_duration_seconds'] ?? 0.06);
        $trimmedPath = $absoluteOutputPath.'.trim.mp3';

        $process = new Process([
            config('klaus.ffmpeg_path'),
            '-y',
            '-i', $absoluteOutputPath,
            '-af', "silenceremove=stop_periods=1:stop_duration={$duration}:stop_threshold={$threshold}dB",
            $trimmedPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($trimmedPath)) {
            return;
        }

        if (! rename($trimmedPath, $absoluteOutputPath)) {
            @unlink($trimmedPath);
        }
    }

    protected function generateSilence(float $seconds, string $absoluteOutputPath): void
    {
        $process = new Process([
            config('klaus.ffmpeg_path'),
            '-y',
            '-f', 'lavfi',
            '-i', 'anullsrc=r=24000:cl=mono',
            '-t', (string) $seconds,
            '-c:a', 'libmp3lame',
            '-q:a', '9',
            $absoluteOutputPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to generate silence: '.$process->getErrorOutput());
        }
    }

    protected function concatFileEntry(string $absolutePath): string
    {
        return "file '".str_replace("'", "'\\''", $absolutePath)."'";
    }
}
