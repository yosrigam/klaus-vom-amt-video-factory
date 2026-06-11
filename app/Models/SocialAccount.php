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
}
