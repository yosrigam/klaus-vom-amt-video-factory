<?php

namespace App\Filament\Resources\HubIdeaResource;

use App\Enums\HubIdeaStatus;
use App\Models\HubIdea;
use App\Support\BackgroundMusic;
use App\Support\BackgroundMusicOptions;
use App\Support\ContentPillarOptions;
use App\Support\HubIdeaFormSteps;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HubIdeaForm
{
    /**
     * @param  (Closure(): array<int, mixed>)|null  $step2HeaderActions
     * @param  (Closure(): array<int, mixed>)|null  $step3HeaderActions
     * @param  (Closure(): array<int, mixed>)|null  $step4HeaderActions
     * @param  (Closure(): array<string, Action>)|null  $productionFieldActions
     * @param  (Closure(?string): void)|null  $onBackgroundMusicChanged
     * @param  (Closure(): Action)|null  $copyImagePromptAction
     * @param  (Closure(): array<int, mixed>)|null  $errorsHeaderActions
     */
    public static function configure(
        Schema $schema,
        ?Closure $applyChatGptJson = null,
        ?Closure $step2HeaderActions = null,
        ?Closure $step3HeaderActions = null,
        ?Closure $step4HeaderActions = null,
        ?Closure $productionFieldActions = null,
        ?Closure $onBackgroundMusicChanged = null,
        ?Closure $onImagePathChanged = null,
        ?Closure $copyImagePromptAction = null,
        ?Closure $errorsHeaderActions = null,
    ): Schema {
        return $schema
            ->columns(1)
            ->components([
                Placeholder::make('workflow_progress')
                    ->label('Workflow progress')
                    ->content(fn (Get $get, ?HubIdea $record): HtmlString => HubIdeaFormSteps::progressHtml($get, $record))
                    ->columnSpanFull(),

                Section::make('Step 1 — Idea')
                    ->description('Import or edit the raw idea. Save before continuing.')
                    ->schema([
                        Select::make('content_pillar')
                            ->label('Content pillar')
                            ->options(ContentPillarOptions::labels()),
                        Textarea::make('idea_text')
                            ->label('Raw idea')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options(collect(HubIdeaStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Step 2 — Script & image prompt')
                    ->description('Copy the prompt into ChatGPT, paste the JSON below — script and image prompt fill in and save automatically.')
                    ->headerActions(static::headerActions($step2HeaderActions))
                    ->schema(fn (Get $get, ?HubIdea $record): array => HubIdeaFormSteps::ideaComplete($get, $record)
                        ? static::contentFields($applyChatGptJson, $copyImagePromptAction)
                        : [static::lockedPlaceholder('step_2_locked', 'Step 1 — Idea')])
                    ->columnSpanFull(),

                Section::make('Step 3 — Image')
                    ->description('Upload an image or use Generate image above.')
                    ->headerActions(static::headerActions($step3HeaderActions))
                    ->schema(fn (Get $get, ?HubIdea $record): array => HubIdeaFormSteps::contentComplete($get, $record)
                        ? static::imageFields($onImagePathChanged)
                        : [static::lockedPlaceholder('step_3_locked', 'Step 2 — Script & image prompt')])
                    ->columnSpanFull(),

                Section::make('Step 4 — Production')
                    ->description('Queue voice, captions, and final video render.')
                    ->headerActions(static::headerActions($step4HeaderActions))
                    ->schema(fn (Get $get, ?HubIdea $record): array => HubIdeaFormSteps::productionUnlocked($get, $record)
                        ? static::productionFields(
                            $productionFieldActions !== null ? $productionFieldActions() : [],
                            $onBackgroundMusicChanged,
                        )
                        : [static::lockedPlaceholder('step_4_locked', 'Step 3 — Image')])
                    ->columnSpanFull(),

                Section::make('Errors')
                    ->visible(fn (Get $get, ?HubIdea $record): bool => filled($record?->error_message))
                    ->headerActions(static::headerActions($errorsHeaderActions))
                    ->schema([
                        Textarea::make('error_message')->rows(3)->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  (Closure(): Action)|null  $copyImagePromptAction
     * @return array<int, mixed>
     */
    private static function contentFields(?Closure $applyChatGptJson, ?Closure $copyImagePromptAction = null): array
    {
        $jsonField = Textarea::make('chatgpt_development_response')
            ->label('Paste ChatGPT JSON response')
            ->placeholder('{"script": "...", "image_prompt": "..."}')
            ->rows(8)
            ->columnSpanFull();

        if ($applyChatGptJson !== null) {
            $jsonField
                ->live(debounce: 700)
                ->afterStateUpdated(function (?string $state, Set $set) use ($applyChatGptJson): void {
                    if (blank($state)) {
                        return;
                    }

                    $applyChatGptJson($state, $set);
                });
        }

        return [
            Textarea::make('prompt_generation_preview')
                ->label('ChatGPT prompt (copy this)')
                ->rows(10)
                ->readOnly()
                ->dehydrated(false)
                ->extraInputAttributes(['data-hub-prompt-generation' => true])
                ->columnSpanFull(),
            $jsonField,
            Textarea::make('script')
                ->label('Voiceover script')
                ->rows(8)
                ->columnSpanFull(),
            static::imagePromptField($copyImagePromptAction),
        ];
    }

    private static function imagePromptField(?Closure $copyImagePromptAction = null): Textarea
    {
        $field = Textarea::make('image_prompt')
            ->label('Image prompt (character + style + scene)')
            ->rows(12)
            ->extraInputAttributes(['data-hub-image-prompt' => true])
            ->columnSpanFull();

        if ($copyImagePromptAction !== null) {
            $field->hintAction($copyImagePromptAction);
        }

        return $field;
    }

    /**
     * @param  (Closure(?string): void)|null  $onImagePathChanged
     * @return array<int, mixed>
     */
    private static function imageFields(?Closure $onImagePathChanged = null): array
    {
        return [
            Flex::make([
                Placeholder::make('image_preview')
                    ->label('Preview')
                    ->content(function (Get $get, ?HubIdea $record): HtmlString {
                        $path = static::resolveImagePath($get, $record);

                        if ($path && Storage::disk('local')->exists($path)) {
                            return static::verticalImagePreview(route('klaus.media', ['path' => $path]));
                        }

                        return static::verticalImagePlaceholder();
                    })
                    ->grow(false),
                FileUpload::make('image_path')
                    ->label('Upload image')
                    ->disk('local')
                    ->directory('klaus/images')
                    ->visibility('private')
                    ->image()
                    ->previewable(false)
                    ->panelLayout('compact')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(10240)
                    ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                        $disk = Storage::disk('local');

                        if (! $disk->exists($file)) {
                            return null;
                        }

                        return [
                            'name' => basename($file),
                            'size' => $disk->size($file),
                            'type' => $disk->mimeType($file),
                            'url' => route('klaus.media', ['path' => $file]),
                        ];
                    })
                    ->openable()
                    ->downloadable()
                    ->getOpenableFileUrlUsing(fn (string $file): string => route('klaus.media', ['path' => $file]))
                    ->getDownloadableFileUrlUsing(fn (string $file): string => route('klaus.media', ['path' => $file]))
                    ->live()
                    ->afterStateUpdated(function (mixed $state, FileUpload $component) use ($onImagePathChanged): void {
                        $hasPendingUpload = collect(Arr::wrap($component->getRawState()))
                            ->contains(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile);

                        if ($hasPendingUpload) {
                            // saveUploadedFiles() calls callAfterStateUpdated() again once paths are stored.
                            $component->saveUploadedFiles();

                            return;
                        }

                        if ($onImagePathChanged === null) {
                            return;
                        }

                        $onImagePathChanged(static::normalizeImagePath($component->getState()));
                    })
                    ->grow(),
            ])
                ->columnSpanFull(),
        ];
    }

    private static function normalizeImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)
                ->flatten()
                ->first(fn (mixed $value): bool => is_string($value) && $value !== '');
        }

        if (! is_string($path) || $path === '') {
            return null;
        }

        return $path;
    }

    private static function resolveImagePath(Get $get, ?HubIdea $record): ?string
    {
        $path = static::normalizeImagePath($get('image_path'));

        if ($path !== null) {
            return $path;
        }

        return $record?->image_path;
    }

    /**
     * @param  array<string, Action>  $actions
     * @param  (Closure(?string): void)|null  $onBackgroundMusicChanged
     * @return array<int, mixed>
     */
    private static function productionFields(array $actions = [], ?Closure $onBackgroundMusicChanged = null): array
    {
        $musicField = static::backgroundMusicField($onBackgroundMusicChanged);

        return [
            Placeholder::make('production_image_path')
                ->label('Image path')
                ->content(fn (Get $get, ?HubIdea $record): string => static::resolveImagePath($get, $record) ?? '—')
                ->columnSpanFull(),
            static::productionRow(
                $actions['voice'] ?? null,
                TextInput::make('audio_path')->label('Voice MP3 path')->disabled()->dehydrated(),
            ),
            static::productionRow(
                $actions['captions'] ?? null,
                TextInput::make('captions_path')->label('Captions path')->disabled()->dehydrated(),
            ),
            static::productionRow(
                $actions['music'] ?? null,
                $musicField,
            ),
            static::productionRow(
                $actions['video'] ?? null,
                TextInput::make('video_path')->label('Video path')->disabled()->dehydrated(),
            ),
            Placeholder::make('audio_preview')
                ->label('Voice MP3 preview')
                ->content(function (?HubIdea $record): HtmlString|string {
                    if (! $record?->audio_path || ! Storage::disk('local')->exists($record->audio_path)) {
                        return 'No voice audio yet.';
                    }

                    $url = route('klaus.media', ['path' => $record->audio_path]);

                    return new HtmlString(
                        '<audio controls style="width:100%;max-width:480px" src="'
                        .e($url)
                        .'"></audio>'
                    );
                })
                ->columnSpanFull(),
            Placeholder::make('video_preview')
                ->label('Video preview')
                ->content(function (?HubIdea $record): HtmlString|string {
                    if (! $record?->video_path || ! Storage::disk('local')->exists($record->video_path)) {
                        return 'No video yet.';
                    }

                    $url = route('klaus.media', ['path' => $record->video_path]);

                    return static::verticalVideoPreview($url);
                })
                ->columnSpanFull(),
        ];
    }

    private static function backgroundMusicField(?Closure $onBackgroundMusicChanged = null): Select|Placeholder
    {
        if (BackgroundMusic::paths() === []) {
            return Placeholder::make('background_music_unavailable')
                ->label('Background music track')
                ->content('No background music tracks configured.');
        }

        $field = Select::make('background_music_path')
            ->label('Background music track')
            ->options(BackgroundMusicOptions::labels())
            ->placeholder('Random at render time')
            ->nullable()
            ->native(false)
            ->helperText('Choose a track or click Random to pick one.');

        if ($onBackgroundMusicChanged !== null) {
            $field
                ->live()
                ->afterStateUpdated(function (?string $state) use ($onBackgroundMusicChanged): void {
                    $onBackgroundMusicChanged($state);
                });
        }

        return $field;
    }

    private static function productionRow(?Action $action, TextInput|Select|Placeholder $field): Flex|TextInput|Select|Placeholder
    {
        $field = $field->grow();

        if ($action === null) {
            return $field->columnSpanFull();
        }

        if ($field instanceof TextInput || $field instanceof Select) {
            return $field
                ->suffixAction($action->button())
                ->columnSpanFull();
        }

        return Flex::make([
            $field,
            $action->button(),
        ])
            ->verticalAlignment(VerticalAlignment::End)
            ->from('md')
            ->columnSpanFull();
    }

    private static function verticalAspectRatioCss(): string
    {
        $width = max(1, (int) config('klaus.video_width', 1080));
        $height = max(1, (int) config('klaus.video_height', 1920));

        return $width.' / '.$height;
    }

    private static function verticalPreviewFrameCss(): string
    {
        $width = static::verticalPreviewWidth();

        return 'width:'.$width.'px;max-width:100%;aspect-ratio:'.static::verticalAspectRatioCss().';border-radius:8px;overflow:hidden;background:rgb(243 244 246);box-shadow:0 1px 2px rgb(0 0 0 / 0.05);';
    }

    private static function verticalPreviewMediaCss(): string
    {
        return 'width:100%;height:100%;object-fit:contain;display:block;';
    }

    private static function verticalPreviewWidth(): int
    {
        return 270;
    }

    private static function verticalImagePreview(string $url): HtmlString
    {
        return new HtmlString(
            '<div style="'.static::verticalPreviewFrameCss().'">'
            .'<img src="'.e($url).'" alt="" style="'.static::verticalPreviewMediaCss().'" />'
            .'</div>'
        );
    }

    private static function verticalImagePlaceholder(): HtmlString
    {
        return new HtmlString(
            '<div style="'.static::verticalPreviewFrameCss().';border:2px dashed rgb(209 213 219);box-shadow:none;display:flex;align-items:center;justify-content:center;text-align:center;padding:1.25rem;color:rgb(107 114 128);">'
            .'<div>'
            .'<div style="font-size:0.875rem;font-weight:600;color:rgb(55 65 81);">No image yet</div>'
            .'<div style="font-size:0.75rem;margin-top:0.35rem;">Use <strong>Generate image</strong> above or upload on the right.</div>'
            .'</div>'
            .'</div>'
        );
    }

    private static function verticalVideoPreview(string $url): HtmlString
    {
        return new HtmlString(
            '<div style="'.static::verticalPreviewFrameCss().'">'
            .'<video controls style="'.static::verticalPreviewMediaCss().'" src="'.e($url).'"></video>'
            .'</div>'
        );
    }

    private static function lockedPlaceholder(string $name, string $previousStep): Placeholder
    {
        return Placeholder::make($name)
            ->hiddenLabel()
            ->content(HubIdeaFormSteps::lockedMessage($previousStep))
            ->columnSpanFull();
    }

    /**
     * @param  (Closure(): array<int, mixed>)|null  $actions
     * @return array<int, Closure>
     */
    private static function headerActions(?Closure $actions): array
    {
        if ($actions === null) {
            return [];
        }

        return [$actions];
    }
}
