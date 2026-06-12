<?php

namespace App\Models;

use App\Enums\HubIdeaStatus;
use Illuminate\Database\Eloquent\Model;
class HubIdea extends Model
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
}
