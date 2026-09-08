<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên sản phẩm')->searchable()->sortable(),
                TextColumn::make('product_type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => config("product.product_type.{$state}", $state)),
                TextColumn::make('unit')->label('Đơn vị'),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->tooltip('Chỉnh sửa sản phẩm'),
            ]);
    }
}
