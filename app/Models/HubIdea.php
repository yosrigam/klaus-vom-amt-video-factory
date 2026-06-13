<?php

namespace App\Models;

use App\Contracts\PublishableVideo;
use App\Enums\HubIdeaStatus;
use App\Enums\SocialPostStatus;
use App\Support\KlausScriptBookends;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HubIdea extends Model implements PublishableVideo
{
    protected $fillable = [
        'idea_text',
        'title',
        'content_pillar',
        'script',
        'image_prompt',
        'chatgpt_development_response',
        'image_path',
        'audio_path',
        'captions_path',
        'background_music_path',
        'video_path',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => HubIdeaStatus::class,
        ];
    }

    public function pillarLabel(): string
    {
        $pillar = config("content_pillars.{$this->content_pillar}");

        return $pillar ? ($pillar['emoji'].' '.$pillar['name']) : (string) $this->content_pillar;
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => HubIdeaStatus::Failed,
            'error_message' => $message,
        ]);
    }

    public function clearError(): void
    {
        $this->update(['error_message' => null]);
    }

    public function hasProductionContent(): bool
    {
        return filled($this->script) && (filled($this->image_prompt) || filled($this->image_path));
    }

    public function syncContentStatus(): void
    {
        if ($this->video_path) {
            $this->update(['status' => HubIdeaStatus::VideoReady]);

            return;
        }

        if ($this->captions_path) {
            $this->update(['status' => HubIdeaStatus::CaptionsReady]);

            return;
        }

        if ($this->audio_path) {
            $this->update(['status' => HubIdeaStatus::VoiceReady]);

            return;
        }

        if ($this->image_path) {
            $this->update(['status' => HubIdeaStatus::ImageReady]);

            return;
        }

        if ($this->hasProductionContent()) {
            $this->update(['status' => HubIdeaStatus::ContentReady]);
        }
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function publishVideoPath(): ?string
    {
        return $this->video_path;
    }

    public function publishTitle(): string
    {
        if (filled($this->title)) {
            return (string) $this->title;
        }

        return Str::limit((string) $this->idea_text, 100, '…');
    }

    public function publishDescription(): string
    {
        $body = KlausScriptBookends::sanitizeBody((string) ($this->script ?? ''));

        if ($body !== '') {
            return Str::limit($body, 5000);
        }

        return Str::limit((string) $this->idea_text, 500);
    }

    public function publishHashtags(): array
    {
        return [];
    }

    public function publishStatusSummary(): string
    {
        $posts = $this->socialPosts()->get();

        if ($posts->isEmpty()) {
            return 'Not published yet.';
        }

        return $posts
            ->map(fn (SocialPost $post): string => sprintf(
                '%s: %s',
                $post->platform->label(),
                $post->status === SocialPostStatus::Published
                    ? ($post->platform_url ?? 'published')
                    : $post->status->value.($post->error_message ? ' — '.$post->error_message : ''),
            ))
            ->implode("\n");
    }
}
