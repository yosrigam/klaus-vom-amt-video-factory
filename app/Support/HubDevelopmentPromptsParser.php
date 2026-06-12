<?php

namespace App\Support;

use InvalidArgumentException;

class HubDevelopmentPromptsParser
{
    /**
     * @return array{script: string, image_prompt: string}
     */
    public static function parse(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new InvalidArgumentException('Paste the ChatGPT JSON response first.');
        }

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Could not parse JSON. Paste valid JSON with script and image_prompt.');
        }

        if (static::isLegacyFormat($decoded)) {
            throw new InvalidArgumentException(
                'This JSON uses the old format (concept_prompt / script_prompt). Click Copy prompt again — the template should return script and image_prompt only, then paste the new ChatGPT response here.',
            );
        }

        $script = trim((string) ($decoded['script'] ?? ''));
        $imagePrompt = trim((string) ($decoded['image_prompt'] ?? ''));

        if ($script === '' || $imagePrompt === '') {
            throw new InvalidArgumentException('JSON must include both script and image_prompt.');
        }

        return [
            'script' => KlausScriptBookends::sanitizeBody($script),
            'image_prompt' => $imagePrompt,
        ];
    }

    /**
     * @param  array{script: string, image_prompt: string}  $parsed
     */
    public static function encode(array $parsed): string
    {
        return json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function isLegacyFormat(array $decoded): bool
    {
        return array_key_exists('concept_prompt', $decoded)
            || array_key_exists('script_prompt', $decoded)
            || array_key_exists('image_prompt_instruction', $decoded);
    }
}
