<?php

namespace App\Filament\Resources;

use App\Enums\WorkflowStep;
use App\Filament\Resources\PromptTemplateResource\Pages;
use App\Models\PromptTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromptTemplateResource extends Resource
{
    protected static ?string $model = PromptTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->maxLength(255),
                Select::make('workflow_step')
                    ->options(collect([
                        WorkflowStep::IdeaGeneration,
                        WorkflowStep::ScriptGeneration,
                        WorkflowStep::ImagePromptGeneration,
                        WorkflowStep::CaptionGeneration,
                        WorkflowStep::SocialPostGeneration,
                    ])->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                Toggle::make('is_active')->default(true),
                Textarea::make('prompt')->required()->rows(16)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('workflow_step')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->since(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromptTemplates::route('/'),
            'create' => Pages\CreatePromptTemplate::route('/create'),
            'edit' => Pages\EditPromptTemplate::route('/{record}/edit'),
        ];
    }
}
