<?php

namespace App\Filament\Resources\HubIdeaResource\Pages;

use App\Enums\HubIdeaStatus;
use App\Filament\Resources\HubIdeaResource;
use App\Models\HubIdea;
use App\Support\BulkVideoIdeaFormat;
use App\Support\ContentPillarOptions;
use App\Support\HubBulkIdeaGenerationPrompt;
use App\Support\HubIdeaBulkParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use InvalidArgumentException;

class ListHubIdeas extends ListRecords
{
    protected static string $resource = HubIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkImportIdeas')
                ->label('Bulk Import')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalIcon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Bulk import ideas')
                ->modalDescription('Select a content pillar, copy the ChatGPT prompt, paste the generated ideas below, then import.')
                ->modalSubmitActionLabel('Import ideas')
                ->modalWidth(Width::ThreeExtraLarge)
                ->registerModalActions([
                    Action::make('copyIdeaGenerationPrompt')
                        ->label('Copy prompt')
                        ->icon(Heroicon::ClipboardDocumentList)
                        ->link()
                        ->alpineClickHandler(function (): string {
                            $message = Js::from('Prompt copied!');

                            return <<<JS
                                (() => {
                                    const form = \$el.closest('form')
                                    const copyText = form?.querySelector('[data-hub-bulk-idea-prompt]')?.value ?? ''

                                    if (! copyText) {
                                        \$tooltip('Select a content pillar first.', {
                                            theme: \$store.theme,
                                            timeout: 2500,
                                        })

                                        return
                                    }

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
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $set(
                                        'idea_generation_prompt',
                                        HubBulkIdeaGenerationPrompt::render($state) ?? 'Select a content pillar to preview the ChatGPT prompt.',
                                    );
                                })
                                ->required()
                                ->columnSpanFull(),
                        ]),
                    Section::make('ChatGPT prompt')
                        ->description('Rendered for the selected pillar. Copy into ChatGPT, then paste the reply into Ideas below.')
                        ->headerActions([
                            $action->getModalAction('copyIdeaGenerationPrompt'),
                        ])
                        ->schema([
                            Textarea::make('idea_generation_prompt')
                                ->label('Prompt')
                                ->placeholder('Select a content pillar to preview the prompt.')
                                ->rows(14)
                                ->readOnly()
                                ->dehydrated(false)
                                ->extraInputAttributes(['data-hub-bulk-idea-prompt' => true])
                                ->columnSpanFull(),
                        ]),
                    Section::make('Ideas')
                        ->description('Paste ChatGPT output here. Each idea must be wrapped in [[ ... ]] with Title, Hook, and Short concept labels.')
                        ->schema([
                            Textarea::make('ideas_text')
                                ->label('Pasted ideas')
                                ->placeholder(BulkVideoIdeaFormat::example())
                                ->rows(14)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    try {
                        $ideas = HubIdeaBulkParser::parse($data['ideas_text']);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($ideas === []) {
                        Notification::make()
                            ->title('No ideas found. Wrap each idea in [[ ... ]] delimiters.')
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach ($ideas as $idea) {
                        HubIdea::query()->create([
                            'content_pillar' => $data['content_pillar'],
                            'idea_text' => $idea['idea_text'],
                            'title' => $idea['title'],
                            'status' => HubIdeaStatus::Draft,
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
