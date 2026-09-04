<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_code')->label('Đơn hàng')->searchable(),
                TextColumn::make('payment_date')->label('Ngày thanh toán')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('amount')->label('Số tiền')->money('VND'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
