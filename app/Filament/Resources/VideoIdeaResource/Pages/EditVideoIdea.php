<?php

namespace App\Filament\Resources\VideoIdeaResource\Pages;

use App\Enums\VideoIdeaStatus;
use App\Filament\Resources\VideoIdeaResource;
use App\Jobs\GenerateCaptionsJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateImagePromptJob;
use App\Jobs\GenerateScriptJob;
use App\Jobs\GenerateVideoJob;
use App\Jobs\GenerateVoiceJob;
use App\Jobs\SchedulePostsJob;
use App\Services\VideoPublishService;
use App\Support\VideoIdeaWorkflow;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVideoIdea extends EditRecord
{
    protected static string $resource = VideoIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateScript')->label('Generate Script')->action(fn () => $this->dispatchJob(GenerateScriptJob::class)),
            Action::make('generateImagePrompt')->label('Generate Image Prompt')->action(fn () => $this->dispatchJob(GenerateImagePromptJob::class)),
            Action::make('generateImage')->label('Generate Image')->action(fn () => $this->dispatchJob(GenerateImageJob::class)),
            Action::make('generateVoice')->label('Generate Voice')->action(fn () => $this->dispatchJob(GenerateVoiceJob::class)),
            Action::make('generateCaptions')->label('Generate Captions')->action(fn () => $this->dispatchJob(GenerateCaptionsJob::class)),
            Action::make('generateVideo')->label('Generate Video')->action(fn () => $this->dispatchJob(GenerateVideoJob::class)),
            Action::make('schedulePosts')->label('Schedule Posts')->action(fn () => $this->dispatchJob(SchedulePostsJob::class)),
            Action::make('publishNow')->label('Publish Now')->color('success')->requiresConfirmation()
                ->action(function () {
                    app(VideoPublishService::class)->dispatchPublishing($this->record);
                    $this->notifySuccess('Publishing queued.');
                }),
            Action::make('retryFailed')->label('Retry Failed Step')->color('warning')
                ->visible(fn () => $this->record->status === VideoIdeaStatus::Failed)
                ->action(function () {
                    VideoIdeaWorkflow::dispatchRetry($this->record);
                    $this->notifySuccess('Retry queued.');
                }),
        ];
    }

    protected function dispatchJob(string $jobClass): void
    {
        $jobClass::dispatch($this->record);
        $this->notifySuccess(class_basename($jobClass).' queued.');
    }

    protected function notifySuccess(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }
}
