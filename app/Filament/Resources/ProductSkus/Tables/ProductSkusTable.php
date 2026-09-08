<?php

namespace App\Filament\Resources\ProductSkus\Tables;

use App\Models\ProductSku;
use App\Services\InventoryManager;
use App\Support\StatusApp;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductSkusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Sản phẩm')->searchable(),
                TextColumn::make('sku_code')->label('Mã SKU')->searchable(),
                TextColumn::make('product.unit')->label('Đơn vị'),
                TextColumn::make('stock')
                    ->label('Tồn khả dụng')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => StatusApp::color('product_sku.inventory_level', 'out_of_stock'),
                        $state <= (int) config('status-app.product_sku.low_stock_threshold', 10) => StatusApp::color('product_sku.inventory_level', 'low_stock'),
                        default => StatusApp::color('product_sku.inventory_level', 'in_stock'),
                    }),
                TextColumn::make('allocated_stock')
                    ->label('Đang cấp cho Order')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái SKU')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusApp::label('product_sku.status', $state))
                    ->color(fn (string $state): string => StatusApp::color('product_sku.status', $state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái SKU')
                    ->options(StatusApp::options('product_sku.status')),
                Filter::make('out_of_stock')
                    ->label('Hết hàng')
                    ->query(fn (Builder $query): Builder => $query->where('stock', 0)),
                Filter::make('low_stock')
                    ->label('Sắp hết')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('stock', '>', 0)
                        ->where('stock', '<=', (int) config('status-app.product_sku.low_stock_threshold', 10))),
            ])
            ->recordActions([
                Action::make('adjustInventory')
                    ->label('Điều chỉnh tồn')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowsUpDown)
                    ->color('warning')
                    ->modalHeading(fn (ProductSku $record): string => "Điều chỉnh tồn SKU {$record->sku_code}")
                    ->schema([
                        Select::make('direction')
                            ->label('Loại điều chỉnh')
                            ->options([
                                'increase' => 'Điều chỉnh tăng',
                                'decrease' => 'Điều chỉnh giảm',
                            ])
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Số lượng')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        Textarea::make('reason')
                            ->label('Lý do')
                            ->rows(3)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (ProductSku $record, array $data): void {
                        $quantity = (int) $data['quantity'];
                        $difference = $data['direction'] === 'decrease' ? -$quantity : $quantity;

                        app(InventoryManager::class)->adjust(
                            $record->id,
                            $difference,
                            $data['reason'],
                            auth()->id(),
                        );

                        Notification::make()
                            ->title('Đã cập nhật tồn kho')
                            ->success()
                            ->send();
                    }),
                Action::make('inventoryHistory')
                    ->label('Lịch sử tồn')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedClock)
                    ->color('info')
                    ->modalHeading(fn (ProductSku $record): string => "Lịch sử tồn SKU {$record->sku_code}")
                    ->modalContent(fn (ProductSku $record) => view('filament.resources.product-skus.actions.inventory-history', [
                        'movements' => $record->inventoryMovements()
                            ->with(['order', 'creator'])
                            ->latest('id')
                            ->limit(100)
                            ->get(),
                    ]))
                    ->modalSubmitAction(false),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare),
            ]);
    }
}
