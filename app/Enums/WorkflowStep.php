<?php

namespace App\Enums;

enum WorkflowStep: string
{
    case IdeaGeneration = 'idea_generation';
    case ScriptGeneration = 'script_generation';
    case ImagePromptGeneration = 'image_prompt_generation';
    case ImageGeneration = 'image_generation';
    case VoiceGeneration = 'voice_generation';
    case CaptionGeneration = 'caption_generation';
    case VideoGeneration = 'video_generation';
    case SocialPostGeneration = 'social_post_generation';
    case Publishing = 'publishing';

    public function label(): string
    {
        return match ($this) {
            self::IdeaGeneration => 'Idea Generation',
            self::ScriptGeneration => 'Script Generation',
            self::ImagePromptGeneration => 'Image Prompt Generation',
            self::ImageGeneration => 'Image Generation',
            self::VoiceGeneration => 'Voice Generation',
            self::CaptionGeneration => 'Caption Generation',
            self::VideoGeneration => 'Video Generation',
            self::SocialPostGeneration => 'Social Post Generation',
            self::Publishing => 'Publishing',
        };
    }
}
