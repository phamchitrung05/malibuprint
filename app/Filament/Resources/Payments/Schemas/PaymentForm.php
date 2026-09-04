<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')->label('Đơn hàng')->relationship('order', 'order_code')->searchable()->preload()->required(),
                DateTimePicker::make('payment_date')->label('Ngày thanh toán')->default(now())->required(),
                TextInput::make('amount')->label('Số tiền')->numeric()->prefix('₫')->required(),
                Select::make('status')->label('Trạng thái')->options(['completed' => 'Đã thanh toán', 'pending' => 'Chờ thanh toán', 'cancelled' => 'Đã hủy'])->default('completed')->required(),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
