<?php

namespace App\Enums;

enum HubIdeaStatus: string
{
    case Draft = 'draft';
    case PromptsReady = 'prompts_ready';
    case ContentReady = 'content_ready';
    case ImageReady = 'image_ready';
    case VoiceReady = 'voice_ready';
    case CaptionsReady = 'captions_ready';
    case VideoReady = 'video_ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PromptsReady => 'Prompts Ready',
            self::ContentReady => 'Content Ready',
            self::ImageReady => 'Image Ready',
            self::VoiceReady => 'Voice Ready',
            self::CaptionsReady => 'Captions Ready',
            self::VideoReady => 'Video Ready',
            self::Failed => 'Failed',
        };
    }
}
