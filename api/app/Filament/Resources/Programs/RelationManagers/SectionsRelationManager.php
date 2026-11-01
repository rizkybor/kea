<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Modules\Catalog\Enums\LessonType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Course builder (ADM-01): modul → lesson, urutan bisa di-drag.
 */
class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Kurikulum';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Judul modul')->required()->maxLength(255)->columnSpanFull(),
                TagsInput::make('topics')->label('Topik (tampil di silabus publik)')->columnSpanFull(),
                Repeater::make('lessons')
                    ->relationship()
                    ->orderColumn('position')
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('title')->required()->columnSpan(2),
                            Select::make('type')->options(LessonType::class)->required(),
                            TextInput::make('duration_minutes')->label('Durasi (menit)')->numeric(),
                            TextInput::make('video_id')->label('Bunny video ID'),
                            Toggle::make('is_preview')->label('Preview gratis')->inline(false),
                        ]),
                        Textarea::make('summary')->label('Ringkasan materi')->rows(3),
                        TextInput::make('resource_url')->label('Link resource / slide')->url(),
                        Grid::make(3)->schema([
                            TextInput::make('assignment_title')->label('Judul tugas'),
                            TextInput::make('assignment_due_day')->label('Deadline (hari ke- dari mulai cohort)')->numeric(),
                            Toggle::make('is_final_project')->label('Final project')->inline(false),
                        ]),
                        Textarea::make('assignment_instructions')->label('Instruksi & rubrik tugas')->rows(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('position')->label('#'),
                TextColumn::make('title')->label('Modul')->searchable(),
                TextColumn::make('lessons_count')->counts('lessons')->label('Lesson'),
            ])
            ->headerActions([
                CreateAction::make()->slideOver(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
