<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Tên sản phẩm')->required(),
                TextInput::make('product_type')->label('Loại sản phẩm')->required()->maxLength(50),
                TextInput::make('unit')->label('Đơn vị')->required()->maxLength(50),
                Toggle::make('is_active')->label('Đang hoạt động')->default(true),
                Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
            ]);
    }
}
