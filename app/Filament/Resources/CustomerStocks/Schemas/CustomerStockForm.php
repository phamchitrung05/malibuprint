<?php

namespace App\Filament\Resources\CustomerStocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CustomerStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Customer Stock được sinh tự động từ Order; schema này chỉ phục vụ khả năng xem dữ liệu về sau.
                Select::make('customer_id')->label('Khách hàng')->relationship('customer', 'name')->disabled(),
                Select::make('order_id')->label('Order')->relationship('order', 'order_code')->disabled(),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
