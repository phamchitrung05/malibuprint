<?php

namespace App\Filament\Resources\ProductSkus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProductSkusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Sản phẩm')->searchable(),
                TextColumn::make('sku_code')->label('Mã SKU')->searchable(),
                TextColumn::make('price')->label('Giá')->money('VND'),
                TextColumn::make('stock')->label('Tồn kho')->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
