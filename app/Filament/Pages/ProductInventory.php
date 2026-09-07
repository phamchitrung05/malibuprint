<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Support\StatusApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductInventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Kho hàng';

    protected static ?string $navigationLabel = 'Tồn kho SKU';

    protected static ?string $title = 'Tồn kho SKU';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $slug = 'product-inventory';

    protected string $view = 'filament.pages.product-inventory';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->withCount('skus')
                    ->withSum('skus as available_stock', 'stock')
                    ->withSum([
                        'inventoryAllocations as allocated_stock' => fn (Builder $query): Builder => $query
                            ->where(
                                'order_inventory_allocations.status',
                                StatusApp::value('inventory_allocation.status', 'allocated'),
                            ),
                    ], 'quantity')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Tên sản phẩm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_type')
                    ->label('Loại')
                    ->badge(),
                TextColumn::make('unit')
                    ->label('Đơn vị'),
                TextColumn::make('skus_count')
                    ->label('Số SKU')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('available_stock')
                    ->label('Tổng tồn khả dụng')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                TextColumn::make('allocated_stock')
                    ->label('Đang cấp cho Order')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('viewSkuInventory')
                    ->label('Xem tồn kho SKU')
                    ->tooltip('Xem tồn kho các SKU của sản phẩm')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedEye)
                    ->color('info')
                    ->modalHeading(fn (Product $record): string => "Tồn kho SKU - {$record->name}")
                    ->modalContent(fn (Product $record) => view('filament.pages.actions.product-sku-inventory', [
                        'product' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->defaultSort('name');
    }
}
