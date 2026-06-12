<?php

namespace App\Support;

class NarrationScriptParser
{
    /**
     * @return array<int, NarrationSegment>
     */
    public static function parse(string $text, NarrationProfile $profile): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $segments = self::extractBeatMarkers($text);

        if (! $profile->preserveLineBreaks) {
            return self::finalizeSegments(self::flattenToSentences($segments), $profile);
        }

        return self::finalizeSegments(self::splitPreservingLineBreaks($segments), $profile);
    }

    /**
     * @return array<int, NarrationSegment>
     */
    private static function extractBeatMarkers(string $text): array
    {
        $markers = config('klaus.beat_markers', []);
        $pattern = '/\[(pause|beat|long_beat)\]/i';
        $segments = [];
        $offset = 0;

        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return [new NarrationSegment(NarrationSegmentKind::Speech, $text)];
        }

        foreach ($matches[0] as $index => $match) {
            $markerPos = $match[1];
            $spoken = trim(substr($text, $offset, $markerPos - $offset));

            if ($spoken !== '') {
                $segments[] = new NarrationSegment(NarrationSegmentKind::Speech, $spoken);
            }

            $markerKey = strtolower($matches[1][$index][0]);
            $pauseSeconds = (float) ($markers[$markerKey] ?? 0.8);
            $segments[] = new NarrationSegment(
                NarrationSegmentKind::Pause,
                pauseSeconds: $pauseSeconds,
            );

            $offset = $markerPos + strlen($match[0]);
        }

        $tail = trim(substr($text, $offset));

        if ($tail !== '') {
            $segments[] = new NarrationSegment(NarrationSegmentKind::Speech, $tail);
        }

        return $segments;
    }

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    private static function splitPreservingLineBreaks(array $segments): array
    {
        $expanded = [];

        foreach ($segments as $segment) {
            if ($segment->isPause()) {
                $expanded[] = $segment;

                continue;
            }

            $lines = preg_split("/\R/u", $segment->text) ?: [$segment->text];
            $pendingLineBreak = false;

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    $pendingLineBreak = true;

                    continue;
                }

                foreach (self::splitSentences($line) as $sentence) {
                    $expanded[] = new NarrationSegment(
                        NarrationSegmentKind::Speech,
                        $sentence,
                        followsLineBreak: $pendingLineBreak,
                    );
                    $pendingLineBreak = false;
                }
            }
        }

        return $expanded;
    }

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    private static function flattenToSentences(array $segments): array
    {
        $expanded = [];

        foreach ($segments as $segment) {
            if ($segment->isPause()) {
                $expanded[] = $segment;

                continue;
            }

            $normalized = preg_replace('/\s+/u', ' ', trim($segment->text)) ?? trim($segment->text);

            foreach (self::splitSentences($normalized) as $sentence) {
                $expanded[] = new NarrationSegment(NarrationSegmentKind::Speech, $sentence);
            }
        }

        return $expanded;
    }

    /**
     * @return array<int, string>
     */
    private static function splitSentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [trim($text)];

        return array_values(array_filter(
            array_map(static fn (string $sentence) => trim($sentence), $sentences),
            static fn (string $sentence) => $sentence !== '',
        ));
    }

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    private static function finalizeSegments(array $segments, NarrationProfile $profile): array
    {
        $speechPhrases = [];

        foreach ($segments as $segment) {
            if ($segment->isPause()) {
                $speechPhrases[] = $segment;

                continue;
            }

            $speechPhrases[] = $segment;
        }

        $merged = self::mergeSpeechSegments($speechPhrases, $profile);

        return array_values(array_map(static function (NarrationSegment $segment): NarrationSegment {
            if ($segment->isPause()) {
                return $segment;
            }

            return new NarrationSegment(
                kind: NarrationSegmentKind::Speech,
                text: $segment->text,
                followsLineBreak: $segment->followsLineBreak,
                isDramatic: self::isDramaticPhrase($segment->text),
            );
        }, $merged));
    }

    /**
     * @param  array<int, NarrationSegment>  $segments
     * @return array<int, NarrationSegment>
     */
    private static function mergeSpeechSegments(array $segments, NarrationProfile $profile): array
    {
        if (! $profile->mergeShortPhrases) {
            return $segments;
        }

        $result = [];
        $group = [];

        $flushGroup = static function () use (&$group, &$result): void {
            if ($group === []) {
                return;
            }

            $merged = ScriptPhrases::mergeShortPhrases(array_map(
                static fn (NarrationSegment $segment) => $segment->text,
                $group,
            ));

            foreach ($merged as $index => $text) {
                $result[] = new NarrationSegment(
                    NarrationSegmentKind::Speech,
                    $text,
                    followsLineBreak: $index === 0 ? $group[0]->followsLineBreak : false,
                );
            }

            $group = [];
        };

        foreach ($segments as $segment) {
            if ($segment->isPause()) {
                $flushGroup();
                $result[] = $segment;

                continue;
            }

            if ($segment->followsLineBreak && $group !== []) {
                $flushGroup();
            }

            $group[] = $segment;
        }

        $flushGroup();

        return $result;
    }

    public static function isDramaticPhrase(string $phrase): bool
    {
        $phrase = trim($phrase);

        if ($phrase === '') {
            return false;
        }

        return str_word_count($phrase) <= 4 || mb_strlen($phrase) <= 25;
    }
}
