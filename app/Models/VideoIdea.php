<?php

namespace App\Models;

use App\Enums\VideoIdeaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoIdea extends Model
{
    protected $fillable = [
        'content_pillar',
        'title',
        'hook',
        'short_concept',
        'status',
        'script',
        'image_prompt',
        'voice_text',
        'captions_path',
        'image_path',
        'audio_path',
        'video_path',
        'publish_title',
        'publish_description',
        'hashtags',
        'scheduled_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoIdeaStatus::class,
            'hashtags' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    public function workflowRuns(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function pillarLabel(): string
    {
        $pillar = config("content_pillars.{$this->content_pillar}");

        return $pillar ? ($pillar['emoji'].' '.$pillar['name']) : $this->content_pillar;
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => VideoIdeaStatus::Failed,
            'error_message' => $message,
        ]);
    }

    public function clearError(): void
    {
        $this->update(['error_message' => null]);
    }
}
