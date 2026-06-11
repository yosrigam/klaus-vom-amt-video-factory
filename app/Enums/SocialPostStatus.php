<?php

namespace App\Enums;

enum SocialPostStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Uploading = 'uploading';
    case Published = 'published';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Scheduled => 'Scheduled',
            self::Uploading => 'Uploading',
            self::Published => 'Published',
            self::Failed => 'Failed',
        };
    }
}
