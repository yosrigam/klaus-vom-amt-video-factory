<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform',
        'name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->platform->label().' — '.$this->name);
    }

    public static function activeForPlatform(SocialPlatform $platform): ?self
    {
        return static::query()
            ->where('platform', $platform)
            ->where('is_active', true)
            ->first();
    }

    public static function resolveForPlatform(SocialPlatform $platform): self
    {
        $account = static::activeForPlatform($platform);

        if ($account !== null) {
            return $account;
        }

        $fromEnvironment = static::credentialsFromEnvironment($platform);

        if ($fromEnvironment === null) {
            throw new RuntimeException(
                "No active {$platform->label()} account. Add one under Social Accounts in the admin, or set the platform credentials in .env.",
            );
        }

        return static::query()->updateOrCreate(
            ['platform' => $platform, 'name' => 'Default (.env)'],
            $fromEnvironment + ['is_active' => true],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function credentialsFromEnvironment(SocialPlatform $platform): ?array
    {
        return match ($platform) {
            SocialPlatform::Youtube => filled(config('services.youtube.refresh_token')) ? [
                'refresh_token' => config('services.youtube.refresh_token'),
            ] : null,
            SocialPlatform::Instagram => filled(config('services.instagram.access_token'))
                && filled(config('services.instagram.ig_user_id')) ? [
                    'access_token' => config('services.instagram.access_token'),
                    'metadata' => ['ig_user_id' => config('services.instagram.ig_user_id')],
                ] : null,
            SocialPlatform::Tiktok => filled(config('services.tiktok.access_token')) ? [
                'access_token' => config('services.tiktok.access_token'),
                'refresh_token' => config('services.tiktok.refresh_token'),
            ] : null,
        };
    }
}
