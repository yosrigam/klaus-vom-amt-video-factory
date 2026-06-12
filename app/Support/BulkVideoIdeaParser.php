<?php

namespace App\Support;

use Illuminate\Support\Str;

class BulkVideoIdeaParser
{
    /**
     * @return array<int, array{title: string, hook: string, short_concept: string}>
     */
    public static function parse(string $text): array
    {
        $ideas = [];

        foreach (self::extractBlocks($text) as $block) {
            $parsed = self::parseBlock(trim($block));

            if ($parsed !== null) {
                $ideas[] = $parsed;
            }
        }

        return $ideas;
    }

    /**
     * @return array<int, string>
     */
    private static function extractBlocks(string $text): array
    {
        preg_match_all('/\[\[(.*?)\]\]/s', $text, $matches);

        return array_values(array_filter(
            array_map('trim', $matches[1] ?? []),
            fn (string $block): bool => $block !== '',
        ));
    }

    /**
     * @return array{title: string, hook: string, short_concept: string}|null
     */
    private static function parseBlock(string $block): ?array
    {
        if ($block === '') {
            return null;
        }

        $json = json_decode($block, true);

        if (is_array($json) && isset($json['title'])) {
            return self::normalizeIdea($json);
        }

        $labeled = self::parseLabeledFields($block);

        if ($labeled !== null) {
            return $labeled;
        }

        return self::parseLines($block);
    }

    /**
     * @return array{title: string, hook: string, short_concept: string}|null
     */
    private static function parseLabeledFields(string $block): ?array
    {
        if (! preg_match('/title\s*:/iu', $block)) {
            return null;
        }

        $fields = [];
        $pattern = '/(title|hook|short\s*concept|concept)\s*:\s*(.*?)(?=\R\s*(?:title|hook|short\s*concept|concept)\s*:|$)/is';

        preg_match_all($pattern, $block, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = strtolower(preg_replace('/\s+/', '_', trim($match[1])));
            $key = $key === 'concept' ? 'short_concept' : $key;
            $fields[$key] = trim($match[2]);
        }

        if (empty($fields['title'])) {
            return null;
        }

        return self::normalizeIdea([
            'title' => $fields['title'],
            'hook' => $fields['hook'] ?? $fields['title'],
            'short_concept' => $fields['short_concept'] ?? $fields['hook'] ?? $fields['title'],
        ]);
    }

    /**
     * @return array{title: string, hook: string, short_concept: string}|null
     */
    private static function parseLines(string $block): ?array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $block)),
            fn (string $line) => $line !== '',
        ));

        if ($lines === []) {
            return null;
        }

        return self::normalizeIdea([
            'title' => $lines[0],
            'hook' => $lines[1] ?? $lines[0],
            'short_concept' => count($lines) >= 3
                ? implode("\n", array_slice($lines, 2))
                : ($lines[1] ?? $lines[0]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $idea
     * @return array{title: string, hook: string, short_concept: string}
     */
    private static function normalizeIdea(array $idea): array
    {
        $title = trim((string) ($idea['title'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Each idea must include a title.');
        }

        $hook = trim((string) ($idea['hook'] ?? $title));
        $shortConcept = trim((string) ($idea['short_concept'] ?? $hook));

        return [
            'title' => Str::limit($title, 255, ''),
            'hook' => Str::limit($hook, 255, ''),
            'short_concept' => $shortConcept,
        ];
    }
}
