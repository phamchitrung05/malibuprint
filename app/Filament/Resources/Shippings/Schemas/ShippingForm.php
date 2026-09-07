<?php

namespace App\Filament\Resources\Shippings\Schemas;

use App\Support\StatusApp;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ShippingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')->label('Đơn hàng')->relationship('order', 'order_code')->searchable()->preload()->required(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(StatusApp::options('shipping.status'))
                    ->default(StatusApp::default('shipping.status'))
                    ->required(),
                DateTimePicker::make('shipped_at')->label('Ngày gửi'),
                DateTimePicker::make('delivered_at')->label('Ngày giao'),
            ]);
    }
}
