<?php

namespace App\Services;

use App\Support\OpenAiHttp;
use RuntimeException;

class OpenAiTextService
{
    public function chat(string $systemPrompt, string $userPrompt, bool $json = true): array|string
    {
        $response = OpenAiHttp::client()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.9,
                'response_format' => $json ? ['type' => 'json_object'] : null,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI text request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if ($content === null) {
            throw new RuntimeException('OpenAI returned empty content.');
        }

        if ($json) {
            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                throw new RuntimeException('OpenAI returned invalid JSON.');
            }

            return $decoded;
        }

        return $content;
    }
}
