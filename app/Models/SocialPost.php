<?php

namespace App\Models;

use App\Contracts\PublishableVideo;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class SocialPost extends Model
{
    protected $fillable = [
        'video_idea_id',
        'hub_idea_id',
        'social_account_id',
        'platform',
        'status',
        'platform_post_id',
        'platform_url',
        'scheduled_at',
        'published_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => SocialPostStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function videoIdea(): BelongsTo
    {
        return $this->belongsTo(VideoIdea::class);
    }

    public function hubIdea(): BelongsTo
    {
        return $this->belongsTo(HubIdea::class);
    }

    public function publishable(): PublishableVideo
    {
        if ($this->hub_idea_id !== null) {
            $hubIdea = $this->hubIdea;

            if ($hubIdea instanceof PublishableVideo) {
                return $hubIdea;
            }
        }

        $videoIdea = $this->videoIdea;

        if ($videoIdea instanceof PublishableVideo) {
            return $videoIdea;
        }

        throw new RuntimeException('Social post is not linked to publishable content.');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => SocialPostStatus::Failed,
            'error_message' => $message,
        ]);
    }
}
