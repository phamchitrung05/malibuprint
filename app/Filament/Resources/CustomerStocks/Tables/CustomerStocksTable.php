<?php

namespace App\Filament\Resources\CustomerStocks\Tables;

use App\Models\CustomerStock;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')->label('Khách hàng')->searchable(),
                TextColumn::make('order.order_code')->label('Mã Order')->searchable()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewStock')
                    ->label('Xem tồn kho')
                    ->tooltip('Xem tồn kho và xuất hàng')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('success')
                    ->modalHeading('Tồn kho và xuất hàng')
                    ->modalContent(fn (CustomerStock $record) => view('filament.resources.customer-stocks.actions.view-stock', [
                        'customerStock' => $record,
                    ]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false),
                Action::make('releaseHistory')
                    ->label('Lịch sử phiếu xuất')
                    ->tooltip('Lịch sử phiếu xuất và xác nhận thu tiền')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedClock)
                    ->color('info')
                    ->modalHeading('Lịch sử phiếu xuất và thanh toán')
                    ->modalContent(fn (CustomerStock $record) => view('filament.resources.customer-stocks.actions.release-history', [
                        'customerStock' => $record,
                    ]))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false),
            ]);
    }
}
