<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaptionService
{
    public function generateFromScript(string $script, string $directory = 'klaus/captions', float $secondsPerLine = 2.8): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($script)) ?: [trim($script)];
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        $entries = [];
        $start = 0.0;

        foreach ($sentences as $sentence) {
            $duration = max(1.5, min(4.5, $secondsPerLine + (strlen($sentence) / 80)));
            $end = $start + $duration;

            $entries[] = [
                'start' => $start,
                'end' => $end,
                'text' => $sentence,
            ];

            $start = $end + 0.15;
        }

        $srt = $this->toSrt($entries);
        $path = $directory.'/'.uniqid('captions_', true).'.srt';
        Storage::disk('local')->put($path, $srt);

        return $path;
    }

    /**
     * @param  array<int, array{start: float, end: float, text: string}>  $entries
     */
    protected function toSrt(array $entries): string
    {
        $lines = [];

        foreach ($entries as $index => $entry) {
            $lines[] = (string) ($index + 1);
            $lines[] = $this->formatTimestamp($entry['start']).' --> '.$this->formatTimestamp($entry['end']);
            $lines[] = Str::upper($entry['text']);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function formatTimestamp(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $millis = (int) round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $millis);
    }
}
