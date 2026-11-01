<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Order hanya-baca: status order diubah oleh webhook/job, bukan manual (integritas pembayaran).
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')->columns(3)->schema([
                    TextEntry::make('invoice_number'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                    TextEntry::make('user.name')->label('Learner'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('customer_phone')->label('WhatsApp')->placeholder('-'),
                    TextEntry::make('subtotal')->money('IDR', locale: 'id'),
                    TextEntry::make('discount')->money('IDR', locale: 'id'),
                    TextEntry::make('total')->money('IDR', locale: 'id'),
                    TextEntry::make('voucher.code')->label('Voucher')->placeholder('-'),
                    TextEntry::make('payment_method')->placeholder('-'),
                    TextEntry::make('paid_at')->dateTime()->placeholder('-'),
                    TextEntry::make('reserved_until')->dateTime()->placeholder('-'),
                    TextEntry::make('created_at')->dateTime(),
                ]),
                Section::make('Item')->schema([
                    RepeatableEntry::make('items')->hiddenLabel()->columns(3)->schema([
                        TextEntry::make('program_title'),
                        TextEntry::make('cohort_name')->placeholder('-'),
                        TextEntry::make('price')->money('IDR', locale: 'id'),
                    ]),
                ]),
                Section::make('Pembayaran gateway')->collapsed()->schema([
                    RepeatableEntry::make('payments')->hiddenLabel()->columns(4)->schema([
                        TextEntry::make('gateway'),
                        TextEntry::make('gateway_ref'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('amount')->money('IDR', locale: 'id'),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')->searchable(),
                TextColumn::make('user.name')->label('Learner')->searchable(),
                TextColumn::make('items.program_title')->label('Program')->limitList(1),
                TextColumn::make('total')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('status')->badge()->color(fn (OrderStatus $state) => match ($state) {
                    OrderStatus::Paid => 'success',
                    OrderStatus::Pending => 'warning',
                    OrderStatus::Refunded => 'info',
                    default => 'gray',
                }),
                TextColumn::make('payment_method')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
