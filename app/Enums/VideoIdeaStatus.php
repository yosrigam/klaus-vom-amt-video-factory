<?php

namespace App\Enums;

enum VideoIdeaStatus: string
{
    case Draft = 'draft';
    case Wip = 'wip';
    case ScriptReady = 'script_ready';
    case ImagePromptReady = 'image_prompt_ready';
    case ImageReady = 'image_ready';
    case VoiceReady = 'voice_ready';
    case CaptionsReady = 'captions_ready';
    case VideoReady = 'video_ready';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Wip => 'WIP',
            self::ScriptReady => 'Script Ready',
            self::ImagePromptReady => 'Image Prompt Ready',
            self::ImageReady => 'Image Ready',
            self::VoiceReady => 'Voice Ready',
            self::CaptionsReady => 'Captions Ready',
            self::VideoReady => 'Video Ready',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Failed => 'Failed',
        };
    }
}
