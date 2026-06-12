<?php

namespace App\Services\Concerns;

use App\Enums\NarrationStyle;
use App\Support\AudioProbe;
use App\Support\KlausScriptBookends;
use App\Support\NarrationProfile;
use App\Support\NarrationScriptParser;
use App\Support\NarrationSegment;
use App\Support\ScriptPhrases;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

trait SynthesizesPhrasesToAudio
{
    protected ?NarrationProfile $narrationProfile = null;

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
        $segments = NarrationScriptParser::parse($text, $profile);

        if ($segments === []) {
            throw new RuntimeException('Voice text is empty.');
        }

        $speechSegments = array_values(array_filter(
            $segments,
            static fn (NarrationSegment $segment) => $segment->isSpeech(),
        ));

        if (count($speechSegments) === 1 && count($segments) === 1) {
            $phrase = $speechSegments[0]->text;
            $this->synthesizePhraseAudio($phrase, $absoluteOutputPath, 0, 1, $phrase);
            $this->trimTrailingSilence($absoluteOutputPath);
            $this->writeTimingSidecar(
                $this->relativePathFromAbsolute($absoluteOutputPath),
                [
                    [
                        'text' => $phrase,
                        'start' => 0.0,
                        'end' => AudioProbe::durationSeconds($absoluteOutputPath) ?? 0.0,
                    ],
                ],
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

                $chunkPath = $disk->path($tmpDir.'/chunk_'.$segmentIndex.'.mp3');
                $this->synthesizePhraseAudio(
                    $segment->text,
                    $chunkPath,
                    $speechIndex,
                    $speechCount,
                    $segment->text,
                );
                $this->trimTrailingSilence($chunkPath);
                $chunkDuration = AudioProbe::durationSeconds($chunkPath) ?? 0.0;

                $phraseTimings[] = [
                    'text' => $segment->text,
                    'start' => round($timelineCursor, 3),
                    'end' => round($timelineCursor + $chunkDuration, 3),
                ];

                $timelineCursor += $chunkDuration;
                $concatEntries[] = $this->concatFileEntry($chunkPath);
                $speechIndex++;

                $nextSegment = $segments[$segmentIndex + 1] ?? null;

                if ($nextSegment === null) {
                    continue;
                }

                $pauseSeconds = $this->pauseBeforeSegment($segment, $nextSegment, $segments, $segmentIndex, $speechCount);

                if ($pauseSeconds > 0) {
                    $pausePath = $disk->path($tmpDir.'/pause_'.$segmentIndex.'.mp3');
                    $this->generateSilence($pauseSeconds, $pausePath);
                    $concatEntries[] = $this->concatFileEntry($pausePath);
                    $timelineCursor += $pauseSeconds;
                }
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
     * @param  array<int, NarrationSegment>  $segments
     */
    protected function pauseBeforeSegment(
        NarrationSegment $current,
        NarrationSegment $next,
        array $segments,
        int $currentIndex,
        int $speechCount,
    ): float {
        if ($next->isPause()) {
            return 0.0;
        }

        if (! $current->isSpeech()) {
            return 0.0;
        }

        $profile = $this->narrationProfile ?? NarrationProfile::for(NarrationStyle::Default);

        if ($next->followsLineBreak) {
            $pause = $profile->lineBreakPauseSeconds;
        } elseif ($currentIndex === 0) {
            $pause = $profile->bookendPauseSeconds;
        } else {
            $speechSegments = array_values(array_filter($segments, static fn (NarrationSegment $segment) => $segment->isSpeech()));
            $speechPositions = [];
            $position = 0;

            foreach ($segments as $index => $segment) {
                if ($segment->isSpeech()) {
                    $speechPositions[$index] = $position;
                    $position++;
                }
            }

            $currentSpeechIndex = $speechPositions[$currentIndex] ?? 0;
            $nextSpeechIndex = $speechPositions[$currentIndex + 1] ?? ($speechCount - 1);
            $nextPhrase = $next->text;

            if (
                $speechCount > 1
                && $nextSpeechIndex === $speechCount - 1
                && KlausScriptBookends::matchesOutro($nextPhrase)
            ) {
                $pause = $profile->bookendPauseSeconds;
            } elseif (
                ScriptPhrases::isShortPhrase($current->text)
                || ScriptPhrases::isShortPhrase($nextPhrase)
            ) {
                $pause = $profile->shortPhrasePauseSeconds;
            } else {
                $pause = $profile->sentencePauseSeconds;
            }
        }

        if ($current->isDramatic && $profile->dramaticPauseMultiplier > 1.0) {
            $pause *= $profile->dramaticPauseMultiplier;
        }

        return $pause;
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
     * @param  array<int, array{text: string, start: float, end: float}>  $phrases
     */
    protected function writeTimingSidecar(string $audioRelativePath, array $phrases): void
    {
        $sidecarPath = AudioProbe::timingSidecarPath($audioRelativePath);

        Storage::disk('local')->put($sidecarPath, json_encode([
            'version' => 1,
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
