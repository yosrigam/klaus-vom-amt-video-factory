<?php

namespace App\Support;

class HubBulkIdeaGenerationPrompt
{
    public static function render(?string $contentPillar): ?string
    {
        return BulkImportPrompt::render($contentPillar);
    }
}
