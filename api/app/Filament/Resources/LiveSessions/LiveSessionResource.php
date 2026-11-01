<?php

namespace App\Filament\Resources\LiveSessions;

use App\Filament\Resources\LiveSessions\Pages\CreateLiveSession;
use App\Filament\Resources\LiveSessions\Pages\EditLiveSession;
use App\Filament\Resources\LiveSessions\Pages\ListLiveSessions;
use App\Modules\Learning\Models\LiveSession;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LiveSessionResource extends Resource
{
    protected static ?string $model = LiveSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelajaran';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cohort_id')
                    ->relationship('cohort', 'name')
                    ->required(),
                Select::make('lesson_id')
                    ->relationship('lesson', 'title'),
                Select::make('mentor_id')
                    ->relationship('mentor', 'name'),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required(),
                TextInput::make('join_url')
                    ->url(),
                TextInput::make('recording_url')
                    ->url(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('cohort.name')
                    ->searchable(),
                TextColumn::make('lesson.title')
                    ->searchable(),
                TextColumn::make('mentor.name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('join_url')
                    ->searchable(),
                TextColumn::make('recording_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLiveSessions::route('/'),
            'create' => CreateLiveSession::route('/create'),
            'edit' => EditLiveSession::route('/{record}/edit'),
        ];
    }
}
