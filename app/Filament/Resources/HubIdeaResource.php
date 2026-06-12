<?php

namespace App\Filament\Resources;

use App\Enums\HubIdeaStatus;
use App\Filament\Resources\HubIdeaResource\HubIdeaForm;
use App\Filament\Resources\HubIdeaResource\Pages;
use App\Jobs\GenerateHubPromptsJob;
use App\Jobs\HubGenerateCaptionsJob;
use App\Jobs\HubGenerateImageJob;
use App\Jobs\HubGenerateVideoJob;
use App\Jobs\HubGenerateVoiceJob;
use App\Models\HubIdea;
use App\Support\ContentPillarOptions;
use App\Support\CopyToClipboardAlpine;
use App\Support\HubIdeaWorkflow;
use App\Support\HubPromptGenerationPrompt;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HubIdeaResource extends Resource
{
    protected static ?string $model = HubIdea::class;

    protected static ?string $navigationLabel = 'Ideas Hub';

    protected static ?string $modelLabel = 'Hub Idea';

    protected static ?string $pluralModelLabel = 'Ideas Hub';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return HubIdeaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('content_pillar')
                    ->label('Pillar')
                    ->formatStateUsing(fn ($state, HubIdea $record) => $record->content_pillar ? $record->pillarLabel() : '—'),
                TextColumn::make('title')->searchable()->limit(40)->placeholder('—'),
                TextColumn::make('idea_text')->searchable()->limit(50),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(HubIdeaStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('content_pillar')->options(ContentPillarOptions::labels()),
            ])
            ->recordActions([
                Action::make('copyPromptGeneration')->label('Copy gen prompt')->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->alpineClickHandler(function (HubIdea $record): string {
                        $prompt = HubPromptGenerationPrompt::render($record->idea_text) ?? '';

                        return CopyToClipboardAlpine::handlerForText(
                            $prompt,
                            'No prompt template available for this idea.',
                            'Prompt generation copied!',
                        );
                    }),
                Action::make('generatePrompts')->label('Content')->icon('heroicon-o-sparkles')
                    ->action(fn (HubIdea $record) => static::queueStep(GenerateHubPromptsJob::dispatch($record), 'Content generation queued.')),
                Action::make('generateImage')->label('Image')->icon('heroicon-o-photo')
                    ->visible(fn (HubIdea $record) => $record->image_prompt && ! $record->image_path)
                    ->action(fn (HubIdea $record) => static::queueStep(HubGenerateImageJob::dispatch($record), 'Image generation queued.')),
                Action::make('generateVoice')->label('Voice')->icon('heroicon-o-microphone')
                    ->visible(fn (HubIdea $record) => (bool) $record->script)
                    ->action(fn (HubIdea $record) => static::queueStep(HubGenerateVoiceJob::dispatch($record), 'Voice generation queued.')),
                Action::make('generateCaptions')->label('Captions')->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->visible(fn (HubIdea $record) => $record->script && $record->audio_path)
                    ->action(fn (HubIdea $record) => static::queueStep(HubGenerateCaptionsJob::dispatch($record), 'Caption generation queued.')),
                Action::make('generateVideo')->label('Video')->icon('heroicon-o-play-circle')
                    ->visible(fn (HubIdea $record) => $record->image_path && $record->audio_path && $record->captions_path)
                    ->action(fn (HubIdea $record) => static::queueStep(HubGenerateVideoJob::dispatch($record), 'Video render queued.')),
                Action::make('retryFailed')->label('Retry')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (HubIdea $record) => $record->status === HubIdeaStatus::Failed)
                    ->action(function (HubIdea $record) {
                        HubIdeaWorkflow::dispatchRetry($record);
                        static::notifyQueued('Retry queued for the failed step.');
                    }),
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHubIdeas::route('/'),
            'edit' => Pages\EditHubIdea::route('/{record}/edit'),
        ];
    }

    protected static function queueStep(mixed $dispatch, string $message): void
    {

        static::notifyQueued($message);
    }

    protected static function notifyQueued(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }
}
