<?php

namespace App\Filament\Resources\Shippings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;

class ShippingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')->label('Đơn hàng')->relationship('order', 'order_code')->searchable()->preload()->required(),
                Select::make('status')->label('Trạng thái')->options(['pending' => 'Chờ giao', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao'])->default('pending')->required(),
                DateTimePicker::make('shipped_at')->label('Ngày gửi'),
                DateTimePicker::make('delivered_at')->label('Ngày giao'),
            ]);
    }
}
