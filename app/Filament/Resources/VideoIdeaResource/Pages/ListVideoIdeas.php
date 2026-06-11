<?php

namespace App\Filament\Resources\VideoIdeaResource\Pages;

use App\Enums\VideoIdeaStatus;
use App\Filament\Resources\VideoIdeaResource;
use App\Jobs\GenerateIdeasJob;
use App\Models\VideoIdea;
use App\Support\BulkVideoIdeaFormat;
use App\Support\BulkVideoIdeaParser;
use App\Support\ContentPillarOptions;
use App\Support\IdeaGenerationPrompt;
use Illuminate\Support\Js;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class ListVideoIdeas extends ListRecords
{
    protected static string $resource = VideoIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateTenIdeas')
                ->label('Generate 10 Ideas')
                ->icon(Heroicon::OutlinedLightBulb)
                ->color('primary')
                ->modalIcon(Heroicon::OutlinedLightBulb)
                ->modalIconColor('primary')
                ->modalHeading('Generate 10 ideas')
                ->modalDescription('Queue automatic idea generation for the selected content pillar, or copy the prompt below into ChatGPT.')
                ->modalSubmitActionLabel('Queue generation')
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema([
                    Section::make('Content pillar')
                        ->schema([
                            Select::make('content_pillar')
                                ->label('Content pillar')
                                ->options(ContentPillarOptions::labels())
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $set('generation_prompt', IdeaGenerationPrompt::render($state) ?? 'No active idea generation prompt template.');
                                })
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Generation prompt')
                        ->description('Rendered from the active idea generation prompt template.')
                        ->schema([
                            Textarea::make('generation_prompt')
                                ->label('Prompt')
                                ->placeholder('Select a content pillar to preview the prompt.')
                                ->rows(16)
                                ->readOnly()
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    GenerateIdeasJob::dispatch($data['content_pillar']);

                    Notification::make()
                        ->title('Idea generation queued (10 ideas).')
                        ->success()
                        ->send();
                }),
            Action::make('bulkImportIdeas')
                ->label('Bulk Import')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalIcon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Bulk import ideas')
                ->modalDescription('Paste manually generated ideas. Wrap each idea in double asterisks on both sides.')
                ->modalSubmitActionLabel('Import ideas')
                ->modalWidth(Width::ThreeExtraLarge)
                ->registerModalActions([
                    Action::make('copyIdeaFormat')
                        ->label('Copy format')
                        ->icon(Heroicon::ClipboardDocumentList)
                        ->link()
                        ->alpineClickHandler(function (): string {
                            $text = Js::from(BulkVideoIdeaFormat::template());
                            $message = Js::from('Format copied!');

                            return <<<JS
                                (() => {
                                    const copyText = {$text}

                                    if (navigator.clipboard?.writeText) {
                                        navigator.clipboard.writeText(copyText)
                                    } else {
                                        const el = document.createElement('textarea')
                                        el.value = copyText
                                        el.style.position = 'fixed'
                                        el.style.top = '0'
                                        el.style.left = '0'
                                        el.style.opacity = '0'
                                        document.body.appendChild(el)
                                        el.focus()
                                        el.select()
                                        document.execCommand('copy')
                                        document.body.removeChild(el)
                                    }

                                    \$tooltip({$message}, {
                                        theme: \$store.theme,
                                        timeout: 2000,
                                    })
                                })()
                            JS;
                        }),
                ])
                ->schema(fn (Action $action): array => [
                    Section::make('Import settings')
                        ->schema([
                            Select::make('content_pillar')
                                ->label('Content pillar')
                                ->options(ContentPillarOptions::labels())
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Ideas')
                        ->description('Each block can use Title, Hook, and Short concept labels, or plain lines.')
                        ->headerActions([
                            $action->getModalAction('copyIdeaFormat'),
                        ])
                        ->schema([
                            Textarea::make('ideas_text')
                                ->label('Pasted ideas')
                                ->placeholder(BulkVideoIdeaFormat::example())
                                ->rows(16)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    try {
                        $ideas = BulkVideoIdeaParser::parse($data['ideas_text']);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($ideas === []) {
                        Notification::make()
                            ->title('No ideas found. Wrap each idea in ** ... ** delimiters.')
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach ($ideas as $idea) {
                        VideoIdea::query()->create([
                            'content_pillar' => $data['content_pillar'],
                            'title' => $idea['title'],
                            'hook' => $idea['hook'],
                            'short_concept' => $idea['short_concept'],
                            'status' => VideoIdeaStatus::Draft,
                        ]);
                    }

                    Notification::make()
                        ->title(count($ideas).' '.str('idea')->plural($ideas).' imported.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
