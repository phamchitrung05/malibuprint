<?php

namespace App\Filament\Resources\OrderItems\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class OrderItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')->label('Đơn hàng')->relationship('order', 'order_code')->searchable()->preload()->required(),
                Select::make('product_sku_id')->label('SKU')->relationship('productSku', 'sku_code')->searchable()->preload()->required(),
                TextInput::make('quantity')->label('Số lượng')->numeric()->default(1)->minValue(1)->required(),
                TextInput::make('unit_price')->label('Đơn giá')->numeric()->required(),
                TextInput::make('subtotal')->label('Thành tiền')->numeric()->required(),
            ]);
    }
}
