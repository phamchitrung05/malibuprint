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
                    // Giao diện Livewire có header riêng nên không hiển thị heading mặc định của Filament.
                    ->modalHeading('')
                    ->modalContent(fn (CustomerStock $record) => view('filament.resources.customer-stocks.actions.view-stock', [
                        'customerStock' => $record,
                    ]))
                    ->modalWidth('7xl')
                     // Dùng chung chiều cao modal để nội dung dài không làm trang nền xuất hiện scrollbar.
                    ->extraModalWindowAttributes(['class' => 'order-view-modal-window lg'])
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ]);
    }
}
