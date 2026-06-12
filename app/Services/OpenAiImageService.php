<?php

namespace App\Services;

use App\Support\KlausImagePrompt;
use App\Support\OpenAiHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiImageService
{
    public function generateAndStore(string $prompt, string $directory = 'klaus/images'): string
    {
        $response = OpenAiHttp::client(180)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => config('services.openai.image_model', 'gpt-image-1'),
                'prompt' => KlausImagePrompt::buildForApi($prompt),
                'size' => '1024x1536',
                'quality' => 'medium',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI image request failed: '.$response->body());
        }

        $b64 = $response->json('data.0.b64_json');
        $url = $response->json('data.0.url');

        if ($b64) {
            $binary = base64_decode($b64, true);

            if ($binary === false) {
                throw new RuntimeException('Failed to decode OpenAI image.');
            }
        } elseif ($url) {
            $binary = Http::timeout(120)->get($url)->body();
        } else {
            throw new RuntimeException('OpenAI image response missing data.');
        }

        $path = $directory.'/'.uniqid('klaus_', true).'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }
}
