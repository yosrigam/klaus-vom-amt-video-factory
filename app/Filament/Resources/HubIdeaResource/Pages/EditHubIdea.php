<?php

namespace App\Filament\Resources\HubIdeaResource\Pages;

use App\Enums\HubIdeaStatus;
use App\Filament\Resources\HubIdeaResource;
use App\Filament\Resources\HubIdeaResource\HubIdeaForm;
use App\Jobs\GenerateHubPromptsJob;
use App\Jobs\HubGenerateCaptionsJob;
use App\Jobs\HubGenerateImageJob;
use App\Jobs\HubGenerateVoiceJob;
use App\Support\BackgroundMusic;
use App\Support\BackgroundMusicOptions;
use App\Support\CopyToClipboardAlpine;
use App\Support\HubDevelopmentPromptsParser;
use App\Support\HubIdeaWorkflow;
use App\Support\HubPromptGenerationPrompt;
use App\Support\KlausImagePrompt;
use App\Support\KlausScriptBookends;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class EditHubIdea extends EditRecord
{
    protected static string $resource = HubIdeaResource::class;

    public function form(Schema $schema): Schema
    {
        return HubIdeaForm::configure(
            $schema,
            $this->applyChatGptJson(...),
            step2HeaderActions: fn (): array => [
                $this->copyPromptAction(),
                $this->generateContentAction(),
            ],
            step3HeaderActions: fn (): array => [
                $this->generateImageAction(),
            ],
            productionFieldActions: fn (): array => [
                'voice' => $this->generateVoiceAction(),
                'captions' => $this->generateCaptionsAction(),
                'music' => $this->chooseRandomBackgroundMusicAction(),
                'video' => $this->produceVideoAction(),
            ],
            onBackgroundMusicChanged: fn (?string $path): mixed => $this->saveBackgroundMusicPath($path),
            onImagePathChanged: fn (?string $path): mixed => $this->saveImagePath($path),
            copyImagePromptAction: fn (): Action => $this->copyImagePromptAction(),
            errorsHeaderActions: fn (): array => [
                $this->retryFailedAction(),
            ],
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function copyPromptAction(): Action
    {
        return Action::make('copyPromptGeneration')
            ->label('Copy prompt')
            ->icon(Heroicon::ClipboardDocumentList)
            ->color('gray')
            ->visible(fn () => filled($this->record->idea_text))
            ->alpineClickHandler(fn (): string => CopyToClipboardAlpine::handler(
                '[data-hub-prompt-generation]',
                'Save the idea text first.',
                'Prompt copied!',
            ));
    }

    protected function copyImagePromptAction(): Action
    {
        return Action::make('copyImagePrompt')
            ->label('Copy')
            ->icon(Heroicon::ClipboardDocumentList)
            ->alpineClickHandler(fn (): string => CopyToClipboardAlpine::handler(
                '[data-hub-image-prompt]',
                'No image prompt yet.',
                'Image prompt copied!',
            ));
    }

    protected function generateContentAction(): Action
    {
        return Action::make('generateContent')
            ->label('Generate via API')
            ->icon(Heroicon::OutlinedSparkles)
            ->action(fn () => $this->dispatchJob(GenerateHubPromptsJob::class));
    }

    protected function generateImageAction(): Action
    {
        return Action::make('generateImage')
            ->label('Generate image')
            ->visible(fn () => $this->record->image_prompt && ! $this->record->image_path)
            ->action(fn () => $this->dispatchJob(HubGenerateImageJob::class));
    }

    protected function generateVoiceAction(): Action
    {
        return Action::make('generateVoice')
            ->label('Generate voiceover')
            ->tooltip('Generate voiceover')
            ->icon(Heroicon::OutlinedMicrophone)
            ->visible(fn () => filled($this->record->script))
            ->action(fn () => $this->dispatchJob(HubGenerateVoiceJob::class));
    }

    protected function generateCaptionsAction(): Action
    {
        return Action::make('generateCaptions')
            ->label('Generate captions')
            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
            ->visible(fn (): bool => filled($this->record->script))
            ->disabled(fn (): bool => blank($this->record->audio_path))
            ->tooltip(fn (): ?string => filled($this->record->audio_path) ? 'Generate captions' : 'Generate voice first')
            ->action(function (): void {
                if (blank($this->record->audio_path)) {
                    Notification::make()->title('Generate voice first.')->warning()->send();

                    return;
                }

                $this->dispatchJob(HubGenerateCaptionsJob::class);
            });
    }

    protected function chooseRandomBackgroundMusicAction(): Action
    {
        return Action::make('chooseRandomBackgroundMusic')
            ->label('Random')
            ->tooltip('Pick a random track and save')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (): bool => BackgroundMusic::paths() !== [])
            ->action(function (): void {
                $path = BackgroundMusic::pick();

                if ($path === null) {
                    Notification::make()->title('No background music tracks available.')->danger()->send();

                    return;
                }

                $this->saveBackgroundMusicPath($path);

                $this->notifySuccess('Background track saved: '.BackgroundMusicOptions::label($path));
            });
    }

    protected function saveBackgroundMusicPath(?string $path): void
    {
        $this->record->update(['background_music_path' => $path]);
        $this->record->refresh();
        $this->fillForm();
    }

    protected function saveImagePath(?string $path): void
    {
        if ($this->record->image_path === $path) {
            return;
        }

        $this->record->update(['image_path' => $path]);
        $this->record->refresh();
        $this->record->syncContentStatus();
        $this->fillForm();
    }

    protected function produceVideoAction(): Action
    {
        return Action::make('produceAll')
            ->label('Produce video')
            ->tooltip('Produce video')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn () => $this->record->script && $this->record->image_path)
            ->action(function (): void {
                try {
                    HubIdeaWorkflow::dispatchProduceAll($this->record);
                    $this->notifySuccess('Voice, captions, and video queued.');
                } catch (\RuntimeException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                }
            });
    }

    protected function retryFailedAction(): Action
    {
        return Action::make('retryFailed')
            ->label('Retry failed step')
            ->color('warning')
            ->visible(fn () => $this->record->status === HubIdeaStatus::Failed)
            ->action(function (): void {
                HubIdeaWorkflow::dispatchRetry($this->record);
                $this->notifySuccess('Retry queued.');
            });
    }

    public function applyChatGptJson(?string $json, ?Set $set = null): void
    {
        $json = trim((string) $json);

        if ($json === '') {
            return;
        }

        try {
            $parsed = HubDevelopmentPromptsParser::parse($json);
        } catch (InvalidArgumentException $exception) {
            if (static::shouldSuppressJsonParseError($json, $exception)) {
                return;
            }

            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        $parsed['script'] = KlausScriptBookends::apply($parsed['script']);
        $storedJson = HubDevelopmentPromptsParser::encode($parsed);
        $fullImagePrompt = KlausImagePrompt::buildFull($parsed['image_prompt']);

        if ($this->record->script === $parsed['script']
            && $this->record->image_prompt === $fullImagePrompt
            && $this->record->chatgpt_development_response === $storedJson) {
            return;
        }

        $this->record->update([
            ...$parsed,
            'image_prompt' => $fullImagePrompt,
            'chatgpt_development_response' => $storedJson,
            'status' => HubIdeaStatus::ContentReady,
        ]);

        if ($set !== null) {
            $set('chatgpt_development_response', $storedJson);
            $set('script', $parsed['script']);
            $set('image_prompt', $fullImagePrompt);
        }

        $this->record->refresh();
        $this->fillForm();

        Notification::make()
            ->title('Script and image prompt saved.')
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['prompt_generation_preview'] = HubPromptGenerationPrompt::render($this->record->idea_text)
            ?? 'No active Ideas Hub prompt generation template.';

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncContentStatus();
        $this->fillForm();
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

    protected static function shouldSuppressJsonParseError(string $json, InvalidArgumentException $exception): bool
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'old format')) {
            return false;
        }

        if (! str_contains($message, 'Could not parse JSON') && ! str_contains($message, 'must include both')) {
            return false;
        }

        return ! str_ends_with(rtrim($json), '}');
    }
}
