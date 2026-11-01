<?php

namespace App\Filament\Resources\Submissions;

use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Modules\Learning\Actions\GradeSubmission;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Models\Submission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * Antrian review tugas (LMS-05). Penilaian lewat action GradeSubmission supaya syarat lulus ikut dievaluasi.
 */
class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pembelajaran';

    protected static ?string $navigationLabel = 'Review tugas';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at')
            ->modifyQueryUsing(fn ($query) => $query->with(['enrollment.user', 'enrollment.program', 'enrollment.cohort', 'lesson']))
            ->columns([
                TextColumn::make('enrollment.user.name')->label('Learner')->searchable(),
                TextColumn::make('enrollment.program.title')->label('Program')
                    ->description(fn (Submission $record) => $record->enrollment->cohort?->name),
                TextColumn::make('lesson.assignment_title')->label('Tugas'),
                TextColumn::make('link_url')->label('Link')->url(fn (Submission $record) => $record->link_url, shouldOpenInNewTab: true)->limit(30),
                TextColumn::make('status')->badge()->color(fn (SubmissionStatus $state) => match ($state) {
                    SubmissionStatus::Reviewed => 'success',
                    SubmissionStatus::Revision => 'danger',
                    SubmissionStatus::Submitted => 'warning',
                }),
                TextColumn::make('score')->label('Nilai')->placeholder('-'),
                TextColumn::make('submitted_at')->dateTime()->sortable()->since(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(SubmissionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)]))
                    ->default(SubmissionStatus::Submitted->value),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('File')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Submission $record) => $record->file_path !== null)
                    ->action(fn (Submission $record) => Storage::disk('local')->download($record->file_path, $record->file_name)),
                Action::make('grade')
                    ->label('Nilai')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (Submission $record) => [
                        'score' => $record->score,
                        'feedback' => $record->feedback,
                        'status' => SubmissionStatus::Reviewed->value,
                    ])
                    ->schema([
                        TextInput::make('score')->label('Nilai (0–100)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Select::make('status')->options([
                            SubmissionStatus::Reviewed->value => 'Lulus review',
                            SubmissionStatus::Revision->value => 'Minta revisi',
                        ])->required(),
                        Textarea::make('feedback')->rows(4),
                    ])
                    ->action(fn (Submission $record, array $data, GradeSubmission $grade) => $grade->handle(
                        $record,
                        auth()->user(),
                        (int) $data['score'],
                        $data['feedback'] ?? null,
                        SubmissionStatus::from($data['status']),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
        ];
    }
}
