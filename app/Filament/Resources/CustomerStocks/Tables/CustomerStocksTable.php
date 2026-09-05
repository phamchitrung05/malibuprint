<?php

namespace App\Filament\Resources\CustomerStocks\Tables;

use App\Models\CustomerStock;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CustomerStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')->label('Khách hàng')->searchable(),
                TextColumn::make('productSku.sku_code')->label('SKU')->searchable(),
                TextColumn::make('quantity')->label('Số lượng')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('releaseStock')
                    ->label('Xuất kho')
                    ->tooltip('Xuất kho')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('success')
                    ->modalHeading('Xuất kho khách hàng')
                    ->modalContent(fn (CustomerStock $record) => view('filament.resources.customer-stocks.actions.release-stock', [
                        'customerStock' => $record,
                    ]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
