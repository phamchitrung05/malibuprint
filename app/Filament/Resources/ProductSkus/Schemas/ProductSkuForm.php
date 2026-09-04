<?php

namespace App\Filament\Resources\ProductSkus\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ProductSkuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->label('Sản phẩm')->relationship('product', 'name')->searchable()->preload()->required(),
                TextInput::make('sku_code')->label('Mã SKU')->required()->unique(ignoreRecord: true)->maxLength(100),
                TextInput::make('price')->label('Giá')->numeric()->prefix('₫')->required(),
                TextInput::make('stock')->label('Tồn kho')->numeric()->default(0),
                Select::make('status')->label('Trạng thái')->options(['active' => 'Đang bán', 'inactive' => 'Ngừng bán'])->default('active')->required(),
            ]);
    }
}
