<?php

namespace App\Filament\Resources\JobApplications;

use App\Filament\Resources\JobApplications\Pages\ListJobApplications;
use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Models\JobApplication;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * Tracking placement (JOB-03): career team memantau & mengoreksi status lamaran. Setiap perubahan tercatat
 * di job_application_events sebagai dasar klaim garansi kerja.
 */
class JobApplicationResource extends Resource
{
    protected static ?string $model = JobApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Karier';

    protected static ?string $navigationLabel = 'Lamaran & placement';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'jobPosting.company', 'events']))
            ->columns([
                TextColumn::make('user.name')->label('Kandidat')->searchable()->description(fn (JobApplication $r) => $r->user->email),
                TextColumn::make('jobPosting.title')->label('Lowongan')->description(fn (JobApplication $r) => $r->jobPosting->company->name),
                TextColumn::make('status')->badge()->color(fn (ApplicationStatus $state) => match ($state) {
                    ApplicationStatus::Hired => 'success',
                    ApplicationStatus::Interview, ApplicationStatus::Offered => 'warning',
                    ApplicationStatus::Rejected, ApplicationStatus::Withdrawn => 'gray',
                    default => 'info',
                }),
                TextColumn::make('events')->label('Catatan terakhir')
                    ->formatStateUsing(fn (JobApplication $record) => $record->events->last()?->note ?? '-')->limit(40),
                TextColumn::make('created_at')->label('Melamar')->date()->sortable(),
                TextColumn::make('hired_at')->label('Diterima')->date()->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ApplicationStatus::class),
                SelectFilter::make('company')->relationship('jobPosting.company', 'name'),
            ])
            ->recordActions([
                Action::make('cv')
                    ->label('CV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (JobApplication $r) => $r->cv_path !== null)
                    ->action(fn (JobApplication $r) => Storage::disk('local')->download($r->cv_path)),
                Action::make('status')
                    ->label('Ubah status')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->schema([
                        Select::make('status')->options(ApplicationStatus::class)->required(),
                        Textarea::make('note')->label('Catatan (mis. bukti offering letter)')->rows(3),
                    ])
                    ->action(fn (JobApplication $record, array $data) => $record->transitionTo(
                        ApplicationStatus::from($data['status']),
                        auth()->user(),
                        $data['note'] ?? null,
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobApplications::route('/'),
        ];
    }
}
