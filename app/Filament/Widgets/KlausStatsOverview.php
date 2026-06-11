<?php

namespace App\Filament\Widgets;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Enums\VideoIdeaStatus;
use App\Enums\WorkflowRunStatus;
use App\Models\SocialPost;
use App\Models\VideoIdea;
use App\Models\WorkflowRun;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KlausStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total ideas', VideoIdea::query()->count()),
            Stat::make('Draft ideas', VideoIdea::query()->where('status', VideoIdeaStatus::Draft)->count())->color('gray'),
            Stat::make('WIP videos', VideoIdea::query()->where('status', VideoIdeaStatus::Wip)->count())->color('warning'),
            Stat::make('Videos ready', VideoIdea::query()->where('status', VideoIdeaStatus::VideoReady)->count())->color('success'),
            Stat::make('Scheduled videos', VideoIdea::query()->where('status', VideoIdeaStatus::Scheduled)->count())->color('info'),
            Stat::make('Published today', VideoIdea::query()->where('status', VideoIdeaStatus::Published)->whereDate('updated_at', today())->count())->color('success'),
            Stat::make('Failed jobs', WorkflowRun::query()->where('status', WorkflowRunStatus::Failed)->count())->color('danger'),
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
