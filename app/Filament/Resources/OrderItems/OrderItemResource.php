<?php

namespace App\Filament\Resources\OrderItems;

use App\Filament\Resources\OrderItems\Pages\ListOrderItems;
use App\Filament\Resources\OrderItems\Schemas\OrderItemForm;
use App\Filament\Resources\OrderItems\Tables\OrderItemsTable;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    // Chi tiết đơn hàng chỉ là dữ liệu phụ trợ, không hiển thị thành mục menu độc lập.
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OrderItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderItemsTable::configure($table);
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
            'index' => ListOrderItems::route('/'),
        ];
    }
}
