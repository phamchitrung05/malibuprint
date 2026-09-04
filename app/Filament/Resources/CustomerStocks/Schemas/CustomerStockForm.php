<?php

namespace App\Filament\Resources\CustomerStocks\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class CustomerStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')->label('Khách hàng')->relationship('customer', 'name')->searchable()->preload()->required(),
                Select::make('product_sku_id')->label('SKU')->relationship('productSku', 'sku_code')->searchable()->preload()->required(),
                TextInput::make('quantity')->label('Số lượng tồn')->numeric()->default(0)->required(),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
