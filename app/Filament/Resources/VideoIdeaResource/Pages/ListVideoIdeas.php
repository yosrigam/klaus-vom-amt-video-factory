<?php

namespace App\Filament\Resources\VideoIdeaResource\Pages;

use App\Filament\Resources\VideoIdeaResource;
use App\Jobs\GenerateIdeasJob;
use App\Support\ContentPillarOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVideoIdeas extends ListRecords
{
    protected static string $resource = VideoIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateTenIdeas')
                ->label('Generate 10 Ideas')
                ->icon('heroicon-o-light-bulb')
                ->schema([
                    Select::make('content_pillar')
                        ->label('Content pillar')
                        ->options(ContentPillarOptions::labels())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    GenerateIdeasJob::dispatch($data['content_pillar']);

                    Notification::make()
                        ->title('Idea generation queued (10 ideas).')
                        ->success()
                        ->send();
                }),
        ];
    }
}
