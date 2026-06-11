<?php

namespace App\Filament\Resources;

use App\Enums\SocialPlatform;
use App\Filament\Resources\SocialAccountResource\Pages;
use App\Models\SocialAccount;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('platform')
                    ->options(collect(SocialPlatform::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                Toggle::make('is_active')->default(true),
                TextInput::make('access_token')->password()->revealable()->columnSpanFull(),
                TextInput::make('refresh_token')->password()->revealable()->columnSpanFull(),
                DateTimePicker::make('token_expires_at'),
                KeyValue::make('metadata')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('name'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('token_expires_at')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialAccounts::route('/'),
            'create' => Pages\CreateSocialAccount::route('/create'),
            'edit' => Pages\EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
