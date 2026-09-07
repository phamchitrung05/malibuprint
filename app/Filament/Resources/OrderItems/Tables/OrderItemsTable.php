<?php

namespace App\Filament\Resources\OrderItems\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_code')->label('Đơn hàng')->searchable(),
                TextColumn::make('productSku.sku_code')->label('SKU')->searchable(),
                TextColumn::make('quantity')->label('Số lượng'),
                TextColumn::make('unit_price')->label('Đơn giá')->money('VND'),
                TextColumn::make('subtotal')->label('Thành tiền')->money('VND'),
            ])
            ->filters([
                //
            ]);
    }
}
