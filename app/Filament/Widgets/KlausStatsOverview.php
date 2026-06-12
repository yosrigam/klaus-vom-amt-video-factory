<?php

namespace App\Filament\Widgets;

use App\Enums\HubIdeaStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\HubIdea;
use App\Models\SocialPost;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KlausStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total ideas', HubIdea::query()->count()),
            Stat::make('Draft ideas', HubIdea::query()->where('status', HubIdeaStatus::Draft)->count())->color('gray'),
            Stat::make('In production', HubIdea::query()->whereIn('status', [
                HubIdeaStatus::ContentReady,
                HubIdeaStatus::ImageReady,
                HubIdeaStatus::VoiceReady,
                HubIdeaStatus::CaptionsReady,
            ])->count())->color('warning'),
            Stat::make('Videos ready', HubIdea::query()->where('status', HubIdeaStatus::VideoReady)->count())->color('success'),
            Stat::make('Failed ideas', HubIdea::query()->where('status', HubIdeaStatus::Failed)->count())->color('danger'),
            Stat::make('YouTube posts', $this->platformSummary(SocialPlatform::Youtube))->description('published / failed / pending'),
            Stat::make('Instagram posts', $this->platformSummary(SocialPlatform::Instagram))->description('published / failed / pending'),
            Stat::make('TikTok posts', $this->platformSummary(SocialPlatform::Tiktok))->description('published / failed / pending'),
        ];
    }

    protected function platformSummary(SocialPlatform $platform): string
    {
        $published = SocialPost::query()->where('platform', $platform)->where('status', SocialPostStatus::Published)->count();
        $failed = SocialPost::query()->where('platform', $platform)->where('status', SocialPostStatus::Failed)->count();
        $pending = SocialPost::query()->where('platform', $platform)->whereIn('status', [SocialPostStatus::Pending, SocialPostStatus::Scheduled, SocialPostStatus::Uploading])->count();

        return "{$published} / {$failed} / {$pending}";
    }
}
