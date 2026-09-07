<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Support\StatusApp;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')->label('Đơn hàng')->relationship('order', 'order_code')->searchable()->preload()->required(),
                DateTimePicker::make('payment_date')->label('Ngày thanh toán')->default(now())->required(),
                TextInput::make('amount')->label('Số tiền')->numeric()->prefix('₫')->required(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(StatusApp::options('payment.status'))
                    ->default(StatusApp::default('payment.status'))
                    ->required(),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
