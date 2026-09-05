<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_code')->label('Mã đơn')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Khách hàng')->searchable(),
                TextColumn::make('order_date')->label('Ngày đặt')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('total_amount')->label('Tổng tiền')->money('VND'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedEye)
                    ->schema([])
                    ->modalHeading('')
                    ->modalWidth('7xl')
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.actions.view-order', [
                        'order' => $record,
                    ])),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare),
                DeleteAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedTrash),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
