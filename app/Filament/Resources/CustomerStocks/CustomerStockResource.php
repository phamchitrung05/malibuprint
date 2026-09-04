<?php

namespace App\Filament\Resources\CustomerStocks;

use App\Filament\Resources\CustomerStocks\Pages\CreateCustomerStock;
use App\Filament\Resources\CustomerStocks\Pages\EditCustomerStock;
use App\Filament\Resources\CustomerStocks\Pages\ListCustomerStocks;
use App\Filament\Resources\CustomerStocks\Schemas\CustomerStockForm;
use App\Filament\Resources\CustomerStocks\Tables\CustomerStocksTable;
use App\Models\CustomerStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerStockResource extends Resource
{
    protected static ?string $model = CustomerStock::class;

    // Tồn kho gắn với khách hàng nên dùng chung mục Custommer.
    protected static string|UnitEnum|null $navigationGroup = 'Custommer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CustomerStockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerStocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerStocks::route('/'),
            'create' => CreateCustomerStock::route('/create'),
            'edit' => EditCustomerStock::route('/{record}/edit'),
        ];
    }
}
