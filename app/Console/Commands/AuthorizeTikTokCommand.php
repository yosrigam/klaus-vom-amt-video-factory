<?php

namespace App\Console\Commands;

use App\Support\TikTokOAuth;
use Illuminate\Console\Command;

class AuthorizeTikTokCommand extends Command
{
    protected $signature = 'tiktok:authorize';

    protected $description = 'Print the TikTok OAuth URL to obtain access and refresh tokens';

    public function handle(): int
    {
        $clientKey = config('services.tiktok.client_key');
        $clientSecret = config('services.tiktok.client_secret');

        if (! $clientKey || ! $clientSecret) {
            $this->error('Set TIKTOK_CLIENT_KEY and TIKTOK_CLIENT_SECRET in .env first.');

            return self::FAILURE;
        }

        $redirectUri = TikTokOAuth::redirectUri();
        $state = bin2hex(random_bytes(16));

        $params = http_build_query([
            'client_key' => $clientKey,
            'scope' => 'video.publish,user.info.basic',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        $authorizeUrl = 'https://www.tiktok.com/v2/auth/authorize/?'.$params;

        $this->line('1. Register this redirect URI in TikTok Developer Portal → your app → Login Kit:');
        $this->info('   '.$redirectUri);
        $this->newLine();
        $this->line('2. Open this URL in your browser and approve access:');
        $this->info('   '.$authorizeUrl);
        $this->newLine();
        $this->line('3. After redirect, copy the tokens shown on the callback page into .env:');
        $this->comment('   TIKTOK_ACCESS_TOKEN=...');
        $this->comment('   TIKTOK_REFRESH_TOKEN=...');
        $this->newLine();
        $this->warn('Authorization codes expire in a few minutes — complete the flow immediately.');

        return self::SUCCESS;
    }
}
