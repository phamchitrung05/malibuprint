<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên dịch vụ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Mã dịch vụ')
                    ->badge(),
                TextColumn::make('product_type')
                    ->label('Danh mục áp dụng')
                    ->formatStateUsing(fn (string $state): string => config("product.product_type.{$state}", $state)),
                TextColumn::make('unit_price')
                    ->label('Đơn giá')
                    ->money('VND')
                    ->sortable(),
                TextColumn::make('order_item_services_count')
                    ->label('Lượt sử dụng')
                    ->counts('orderItemServices')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->tooltip('Chỉnh sửa dịch vụ'),
            ]);
    }
}
