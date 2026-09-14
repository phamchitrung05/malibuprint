<?php

namespace App\Filament\Resources\ShippingProviders\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên đơn vị')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')->label('Số điện thoại')->searchable(),
                TextColumn::make('orders_count')->label('Số Order sử dụng')->counts('orders')->numeric(),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare),
            ]);
    }
}
