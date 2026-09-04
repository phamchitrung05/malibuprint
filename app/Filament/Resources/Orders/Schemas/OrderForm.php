<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Mã được sinh tự động trong CreateOrder nên người dùng chỉ xem, không nhập thủ công.
                TextInput::make('order_code')->label('Mã đơn hàng')->default('Tự động sau khi lưu')->disabled()->dehydrated(false),
                Select::make('customer_id')->label('Khách hàng')->relationship('customer', 'name')->searchable()->preload()->required(),
                DateTimePicker::make('order_date')->label('Ngày đặt')->default(now())->required(),
                Select::make('status')->label('Trạng thái')->options(['pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'])->default('pending')->required(),
                TextInput::make('subtotal')->label('Tạm tính')->numeric()->default(0),
                TextInput::make('discount')->label('Giảm giá')->numeric()->default(0),
                TextInput::make('total_amount')->label('Tổng tiền')->numeric()->default(0)->required(),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
