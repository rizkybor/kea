<?php

namespace App\Filament\Resources\JobPostings;

use App\Filament\Resources\JobPostings\Pages\CreateJobPosting;
use App\Filament\Resources\JobPostings\Pages\EditJobPosting;
use App\Filament\Resources\JobPostings\Pages\ListJobPostings;
use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Career\Models\JobPosting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Karier';

    protected static ?string $navigationLabel = 'Lowongan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lowongan')->columns(2)->schema([
                Select::make('company_id')->relationship('company', 'name')->required()->searchable()->preload(),
                TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state).'-'.Str::lower(Str::random(5))) : null),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('category_id')->relationship('category', 'name'),
                Select::make('employment_type')->options(EmploymentType::class)->required(),
                Select::make('work_mode')->options(WorkMode::class)->required(),
                TextInput::make('location'),
                Toggle::make('alumni_only')->label('Khusus alumni KEA')->default(true)->inline(false),
                Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                TagsInput::make('requirements')->label('Kualifikasi')->columnSpanFull(),
                TagsInput::make('skills')->label('Skill')->columnSpanFull(),
            ]),
            Section::make('Gaji & publikasi')->columns(3)->schema([
                TextInput::make('salary_min')->numeric()->prefix('Rp'),
                TextInput::make('salary_max')->numeric()->prefix('Rp'),
                Toggle::make('show_salary')->label('Tampilkan gaji')->inline(false),
                Select::make('status')->options(JobStatus::class)->required()->default(JobStatus::Draft->value),
                DateTimePicker::make('published_at'),
                DateTimePicker::make('closes_at'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->description(fn (JobPosting $record) => $record->company->name),
                TextColumn::make('employment_type')->badge(),
                TextColumn::make('work_mode')->badge(),
                TextColumn::make('status')->badge()->color(fn (JobStatus $state) => match ($state) {
                    JobStatus::Open => 'success',
                    JobStatus::Draft => 'gray',
                    JobStatus::Closed => 'danger',
                }),
                TextColumn::make('applications_count')->counts('applications')->label('Pelamar')->sortable(),
                IconColumn::make('alumni_only')->label('Alumni')->boolean(),
                TextColumn::make('closes_at')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(JobStatus::class),
                SelectFilter::make('company')->relationship('company', 'name'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobPostings::route('/'),
            'create' => CreateJobPosting::route('/create'),
            'edit' => EditJobPosting::route('/{record}/edit'),
        ];
    }
}
