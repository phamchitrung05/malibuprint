<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_code')->label('Đơn hàng')->searchable(),
                TextColumn::make('stockRelease.release_code')
                    ->label('Phiếu xuất')
                    ->placeholder('Thanh toán toàn Order'),
                TextColumn::make('payment_date')->label('Ngày thanh toán')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('amount')->label('Số tiền')->money('VND'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
            ])
            ->filters([
                //
            ]);
    }
}
