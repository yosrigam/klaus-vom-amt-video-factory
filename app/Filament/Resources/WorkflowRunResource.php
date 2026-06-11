<?php

namespace App\Filament\Resources;

use App\Enums\WorkflowRunStatus;
use App\Enums\WorkflowStep;
use App\Filament\Resources\WorkflowRunResource\Pages;
use App\Models\WorkflowRun;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkflowRunResource extends Resource
{
    protected static ?string $model = WorkflowRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('video_idea_id')->relationship('videoIdea', 'title'),
                Select::make('workflow_step')
                    ->options(collect(WorkflowStep::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Select::make('status')
                    ->options(collect(WorkflowRunStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Textarea::make('error_message')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('videoIdea.title')->label('Video idea'),
                TextEntry::make('workflow_step')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
                TextEntry::make('error_message')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('videoIdea.title')->label('Video')->limit(30),
                TextColumn::make('workflow_step')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('started_at')->dateTime()->sortable(),
                TextColumn::make('finished_at')->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(WorkflowRunStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('workflow_step')
                    ->options(collect(WorkflowStep::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowRuns::route('/'),
            'view' => Pages\ViewWorkflowRun::route('/{record}'),
        ];
    }
}
