<?php

namespace App\Filament\Resources;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Filament\Resources\SocialPostResource\Pages;
use App\Models\SocialPost;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SocialPostResource extends Resource
{
    protected static ?string $model = SocialPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('video_idea_id')->relationship('videoIdea', 'title')->required(),
                Select::make('social_account_id')->relationship('socialAccount', 'name')->required(),
                Select::make('platform')
                    ->options(collect(SocialPlatform::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                    ->required(),
                Select::make('status')
                    ->options(collect(SocialPostStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                TextInput::make('platform_post_id'),
                TextInput::make('platform_url')->url(),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('published_at'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('videoIdea.title')->label('Video')->limit(30),
                TextColumn::make('platform')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('platform_url')->url(fn ($state) => $state, shouldOpenInNewTab: true)->limit(20),
                TextColumn::make('scheduled_at')->dateTime(),
                TextColumn::make('published_at')->since(),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->options(collect(SocialPlatform::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])),
                SelectFilter::make('status')
                    ->options(collect(SocialPostStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialPosts::route('/'),
            'edit' => Pages\EditSocialPost::route('/{record}/edit'),
        ];
    }
}
