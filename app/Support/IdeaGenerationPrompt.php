<?php

namespace App\Support;

use App\Enums\WorkflowStep;
use App\Models\PromptTemplate;

class IdeaGenerationPrompt
{
    public static function template(): ?PromptTemplate
    {
        return PromptTemplate::activeFor(WorkflowStep::IdeaGeneration);
    }

    public static function render(?string $contentPillar): ?string
    {
        if (! $contentPillar) {
            return null;
        }

        $pillar = config("content_pillars.{$contentPillar}");
        $template = static::template();

        if (! $pillar || ! $template) {
            return null;
        }

        return $template->render([
            'pillar_name' => $pillar['name'],
            'pillar_description' => $pillar['description'],
            'pillar_examples' => implode(', ', $pillar['examples']),
            'klaus_angle' => $pillar['klaus_angle'],
            'disclaimer' => config('klaus.disclaimer'),
        ]);
    }
}
