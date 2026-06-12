<?php

namespace App\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

class HubIdeaBulkParser
{
    /**
     * @return list<array{idea_text: string, title: string}>
     */
    public static function parse(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new InvalidArgumentException('Paste at least one idea.');
        }

        if (str_contains($text, '[[')) {
            return self::parseStructuredIdeas($text);
        }

        return self::parsePlainIdeas($text);
    }

    /**
     * @return list<array{idea_text: string, title: string}>
     */
    private static function parseStructuredIdeas(string $text): array
    {
        $parsed = BulkVideoIdeaParser::parse($text);

        if ($parsed === []) {
            return [];
        }

        return array_map(
            fn (array $idea): array => [
                'title' => $idea['title'],
                'idea_text' => self::composeIdeaText($idea),
            ],
            $parsed,
        );
    }

    /**
     * @param  array{title: string, hook: string, short_concept: string}  $idea
     */
    public static function composeIdeaText(array $idea): string
    {
        return implode("\n\n", [
            'Title: '.$idea['title'],
            'Premise:',
            $idea['short_concept'],
        ]);
    }

    /**
     * @return list<array{idea_text: string, title: string}>
     */
    private static function parsePlainIdeas(string $text): array
    {
        $chunks = preg_split('/\n\s*\n+/', $text) ?: [];

        if (count($chunks) === 1 && ! str_contains($text, "\n\n")) {
            $chunks = preg_split('/\r?\n/', $text) ?: [];
        }

        $ideas = [];

        foreach ($chunks as $chunk) {
            $ideaText = trim($chunk);

            if ($ideaText === '') {
                continue;
            }

            $ideas[] = [
                'idea_text' => $ideaText,
                'title' => Str::limit($ideaText, 60, ''),
            ];
        }

        return $ideas;
    }
}
