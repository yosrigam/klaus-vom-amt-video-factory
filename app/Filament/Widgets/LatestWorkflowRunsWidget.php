<?php

namespace App\Filament\Widgets;

use App\Models\WorkflowRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestWorkflowRunsWidget extends TableWidget
{
    protected static ?string $heading = 'Latest workflow runs';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(WorkflowRun::query()->latest('id')->limit(10))
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('videoIdea.title')->label('Video')->limit(25),
                TextColumn::make('workflow_step')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('started_at')->since(),
                TextColumn::make('error_message')->limit(40)->toggleable(),
            ])
            ->paginated(false);
    }
}
