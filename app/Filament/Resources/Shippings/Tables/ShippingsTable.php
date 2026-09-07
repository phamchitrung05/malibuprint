<?php

namespace App\Filament\Resources\Shippings\Tables;

use App\Support\StatusApp;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_code')->label('Đơn hàng')->searchable(),
                TextColumn::make('stockRelease.release_code')
                    ->label('Phiếu xuất')
                    ->placeholder('Giao toàn bộ Order'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusApp::label('shipping.status', $state))
                    ->color(fn (string $state): string => StatusApp::color('shipping.status', $state)),
                TextColumn::make('shipped_at')->label('Ngày gửi')->dateTime('d/m/Y H:i'),
                TextColumn::make('delivered_at')->label('Ngày giao')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ]);
    }
}
