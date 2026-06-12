<?php

namespace App\Http\Controllers;

use App\Support\TikTokOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class TikTokOAuthController extends Controller
{
    public function callback(Request $request): View
    {
        if ($request->filled('error')) {
            return view('tiktok.oauth-result', [
                'success' => false,
                'message' => $request->query('error_description', $request->query('error')),
            ]);
        }

        $code = $request->query('code');

        if (! $code) {
            return view('tiktok.oauth-result', [
                'success' => false,
                'message' => 'No authorization code received. Start the flow with: php artisan tiktok:authorize',
            ]);
        }

        $redirectUri = TikTokOAuth::redirectUri();

        $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error_description') ?? $response->json('error') ?? $response->body();

            return view('tiktok.oauth-result', [
                'success' => false,
                'message' => $error,
            ]);
        }

        return view('tiktok.oauth-result', [
            'success' => true,
            'accessToken' => $response->json('access_token'),
            'refreshToken' => $response->json('refresh_token'),
            'expiresIn' => $response->json('expires_in'),
            'scope' => $response->json('scope'),
            'redirectUri' => $redirectUri,
        ]);
    }
}
