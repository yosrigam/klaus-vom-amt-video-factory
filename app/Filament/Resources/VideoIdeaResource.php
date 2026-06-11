<?php

namespace App\Filament\Resources;

use App\Enums\VideoIdeaStatus;
use App\Filament\Resources\VideoIdeaResource\Pages;
use App\Jobs\GenerateCaptionsJob;
use App\Jobs\GenerateIdeasJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateImagePromptJob;
use App\Jobs\GenerateScriptJob;
use App\Jobs\GenerateVideoJob;
use App\Jobs\GenerateVoiceJob;
use App\Jobs\SchedulePostsJob;
use App\Models\VideoIdea;
use App\Services\VideoPublishService;
use App\Support\ContentPillarOptions;
use App\Support\VideoIdeaWorkflow;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideoIdeaResource extends Resource
{
    protected static ?string $model = VideoIdea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Idea')->schema([
                Select::make('content_pillar')
                    ->options(ContentPillarOptions::labels())
                    ->required(),
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('hook')->required()->maxLength(255),
                Textarea::make('short_concept')->required()->rows(3),
                Select::make('status')
                    ->options(collect(VideoIdeaStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
            ])->columns(2),
            Section::make('Production')->schema([
                Textarea::make('script')->rows(6)->columnSpanFull(),
                Textarea::make('image_prompt')->rows(4)->columnSpanFull(),
                Textarea::make('voice_text')->rows(4)->columnSpanFull(),
                TextInput::make('image_path'),
                TextInput::make('audio_path'),
                TextInput::make('captions_path'),
                TextInput::make('video_path'),
            ])->columns(2),
            Section::make('Publishing')->schema([
                TextInput::make('publish_title')->maxLength(255),
                Textarea::make('publish_description')->rows(3),
                TagsInput::make('hashtags'),
                DateTimePicker::make('scheduled_at'),
            ])->columns(2),
            Section::make('Errors')->schema([
                Textarea::make('error_message')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('content_pillar')
                    ->label('Pillar')
                    ->formatStateUsing(fn ($state, VideoIdea $record) => $record->pillarLabel()),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('scheduled_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(VideoIdeaStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('content_pillar')->options(ContentPillarOptions::labels()),
            ])
            ->recordActions([
                Action::make('generateScript')->label('Script')->icon('heroicon-o-document-text')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateScriptJob::dispatch($record), 'Script generation queued.')),
                Action::make('generateImagePrompt')->label('Img Prompt')->icon('heroicon-o-sparkles')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateImagePromptJob::dispatch($record), 'Image prompt queued.')),
                Action::make('generateImage')->label('Image')->icon('heroicon-o-photo')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateImageJob::dispatch($record), 'Image generation queued.')),
                Action::make('generateVoice')->label('Voice')->icon('heroicon-o-microphone')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateVoiceJob::dispatch($record), 'Voice generation queued.')),
                Action::make('generateCaptions')->label('Captions')->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateCaptionsJob::dispatch($record), 'Caption generation queued.')),
                Action::make('generateVideo')->label('Video')->icon('heroicon-o-play-circle')
                    ->action(fn (VideoIdea $record) => static::queueStep(GenerateVideoJob::dispatch($record), 'Video render queued.')),
                Action::make('schedulePosts')->label('Schedule')->icon('heroicon-o-calendar-days')
                    ->action(fn (VideoIdea $record) => static::queueStep(SchedulePostsJob::dispatch($record), 'Social posts scheduled.')),
                Action::make('publishNow')->label('Publish')->icon('heroicon-o-rocket-launch')->color('success')
                    ->requiresConfirmation()
                    ->action(function (VideoIdea $record) {
                        app(VideoPublishService::class)->dispatchPublishing($record);
                        static::notifyQueued('Publishing queued for all active platforms.');
                    }),
                Action::make('retryFailed')->label('Retry')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (VideoIdea $record) => $record->status === VideoIdeaStatus::Failed)
                    ->action(function (VideoIdea $record) {
                        VideoIdeaWorkflow::dispatchRetry($record);
                        static::notifyQueued('Retry queued for the failed step.');
                    }),
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideoIdeas::route('/'),
            'edit' => Pages\EditVideoIdea::route('/{record}/edit'),
        ];
    }

    protected static function queueStep(mixed $dispatch, string $message): void
    {
        $dispatch;
        static::notifyQueued($message);
    }

    protected static function notifyQueued(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }
}
