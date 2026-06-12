<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenAiHttp
{
    public static function client(int $timeout = 120): PendingRequest
    {
        $request = Http::withToken(config('services.openai.api_key'))
            ->timeout($timeout);

        $organization = config('services.openai.organization');

        if (is_string($organization) && $organization !== '') {
            $request = $request->withHeaders([
                'OpenAI-Organization' => $organization,
            ]);
        }

        return $request;
    }
}
