<?php

namespace App\Filament\Resources\ProductSkus;

use App\Filament\Resources\ProductSkus\Pages\CreateProductSku;
use App\Filament\Resources\ProductSkus\Pages\EditProductSku;
use App\Filament\Resources\ProductSkus\Pages\ListProductSkus;
use App\Filament\Resources\ProductSkus\Schemas\ProductSkuForm;
use App\Filament\Resources\ProductSkus\Tables\ProductSkusTable;
use App\Models\ProductSku;
use App\Support\StatusApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductSkuResource extends Resource
{
    protected static ?string $model = ProductSku::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Kho hàng';

    protected static ?string $navigationLabel = 'Tồn kho SKU';

    protected static ?string $modelLabel = 'SKU tồn kho';

    protected static ?string $pluralModelLabel = 'Tồn kho tất cả SKU';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductSkuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSkusTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // Lượng đang cấp chỉ gồm allocation của Order chưa hoàn thành hoặc chưa hủy.
        return parent::getEloquentQuery()
            ->with('product')
            ->withSum([
                'inventoryAllocations as allocated_stock' => fn (Builder $query): Builder => $query
                    ->where('status', StatusApp::value('inventory_allocation.status', 'allocated')),
            ], 'quantity');
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
            'index' => ListProductSkus::route('/'),
            'create' => CreateProductSku::route('/create'),
            'edit' => EditProductSku::route('/{record}/edit'),
        ];
    }
}
