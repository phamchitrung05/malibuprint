<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Tên khách hàng')->required(),
                TextInput::make('phone')->label('Số điện thoại')->tel()->required()->maxLength(30),
                Textarea::make('address')->label('Địa chỉ'),
                Toggle::make('is_active')->label('Đang hoạt động')->default(true),
                Textarea::make('note')->label('Ghi chú'),
            ]);
    }
}
