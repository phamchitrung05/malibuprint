<?php

namespace App\Filament\Resources\ProductSkus\Schemas;

use App\Support\StatusApp;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductSkuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->label('Sản phẩm')->relationship('product', 'name')->searchable()->preload()->required(),
                TextInput::make('sku_code')->label('Mã SKU')->required()->unique(ignoreRecord: true)->maxLength(100),
                TextInput::make('price')->label('Giá')->numeric()->prefix('₫')->required(),
                TextInput::make('stock')
                    ->label('Tồn đầu kỳ')
                    ->helperText('Sau khi tạo SKU, hãy dùng thao tác Điều chỉnh tồn để mọi thay đổi có lịch sử.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->disabledOn('edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(StatusApp::options('product_sku.status'))
                    ->default(StatusApp::default('product_sku.status'))
                    ->required(),
            ]);
    }
}
