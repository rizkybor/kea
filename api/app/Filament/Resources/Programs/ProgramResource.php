<?php

namespace App\Filament\Resources\Programs;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\RelationManagers\SectionsRelationManager;
use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Models\Program;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas program')->columns(2)->schema([
                    TextInput::make('title')->required()->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true)->helperText('URL: /program/{slug}'),
                    Select::make('category_id')->relationship('category', 'name')->required(),
                    Select::make('type')->options(ProgramType::class)->required(),
                    TextInput::make('code')->required()->maxLength(12)->helperText('Dipakai di kode sertifikat, mis. FSWD'),
                    Select::make('level')->options(['pemula' => 'Pemula', 'menengah' => 'Menengah', 'mahir' => 'Mahir'])->default('pemula')->required(),
                    TextInput::make('duration_label')->placeholder('4 bulan'),
                    TextInput::make('format_label')->placeholder('Live online'),
                    Textarea::make('summary')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
                ]),
                Section::make('Harga')->columns(3)->schema([
                    TextInput::make('price')->label('Harga normal')->required()->numeric()->prefix('Rp'),
                    TextInput::make('promo_price')->label('Harga promo')->numeric()->prefix('Rp'),
                    TextInput::make('installment_from')->label('Cicilan mulai /bulan')->numeric()->prefix('Rp'),
                    Toggle::make('has_job_guarantee')->label('Garansi kerja'),
                    Toggle::make('is_published')->label('Tayang di katalog'),
                ]),
                Section::make('Konten halaman')->columns(2)->collapsed()->schema([
                    TextInput::make('thumbnail_url')->url(),
                    TextInput::make('preview_video_url')->url(),
                    Repeater::make('benefits')->label('Yang kamu dapat')->schema([
                        TextInput::make('title')->required(),
                        TextInput::make('body')->required(),
                    ])->columns(2)->columnSpanFull(),
                    Repeater::make('faqs')->label('FAQ')->schema([
                        TextInput::make('q')->label('Pertanyaan')->required(),
                        Textarea::make('a')->label('Jawaban')->required()->rows(2),
                    ])->columnSpanFull(),
                    TextInput::make('rating')->numeric()->minValue(0)->maxValue(5),
                    TextInput::make('rating_count')->numeric()->default(0),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->description(fn (Program $record) => $record->slug),
                TextColumn::make('type')->badge(),
                TextColumn::make('category.name'),
                TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id')->sortable()
                    ->description(fn (Program $record) => $record->promo_price ? 'Promo Rp'.number_format($record->promo_price, 0, ',', '.') : null),
                TextColumn::make('enrolled_count')->label('Peserta')->numeric()->sortable(),
                IconColumn::make('is_published')->label('Tayang')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options(ProgramType::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
