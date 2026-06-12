<?php

namespace App\Support;

class BulkImportPrompt
{
    public static function render(?string $contentPillar): ?string
    {
        if (! $contentPillar) {
            return null;
        }

        $pillar = config("content_pillars.{$contentPillar}");

        if (! $pillar) {
            return null;
        }

        $pillarName = $pillar['name'];
        $pillarDescription = $pillar['description'];
        $pillarExamples = implode(', ', $pillar['examples']);
        $klausAngle = $pillar['klaus_angle'];
        $disclaimer = config('klaus.disclaimer');
        $formatExample = BulkVideoIdeaFormat::example();

        return <<<PROMPT
You are generating short-form video ideas for Klaus vom Amt — a satirical TikTok, Reels, and Shorts character.

## About Klaus vom Amt
Klaus is a 53-year-old German civil servant who treats everyday life like an administrative tribunal. He speaks in deadpan, passive-aggressive bureaucratic German humor: mildly disappointed, sarcastic, darkly funny, never cheerful. Videos are 25–40 seconds. Entertainment and satire only. {$disclaimer}

## Content pillar for this batch
Pillar: {$pillarName}
What this pillar covers: {$pillarDescription}
Example topics: {$pillarExamples}
Klaus angle: {$klausAngle}

## Your task
Generate exactly 10 unique video ideas for this pillar. Each idea must work as a standalone Klaus sketch.

For every idea, write:
- Title — short internal working title (not the social publish title)
- Hook — punchy opening line for the first 1–2 seconds on screen
- Short concept — 1–2 sentences describing the scenario Klaus performs

## Output format (critical)
I will paste your reply directly into a video production admin dashboard. Return ONLY the ideas — no introduction, no numbered list, no JSON, no markdown headings.

Wrap each idea in double square brackets on both sides ([[ ... ]]). Use Title, Hook, and Short concept labels exactly like this:

{$formatExample}

Generate all 10 ideas now.
PROMPT;
    }
}
