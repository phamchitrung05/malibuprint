<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Services\InventoryManager;
use App\Support\StatusApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $slug = 'product-inventory';

    protected string $view = 'filament.pages.product-inventory';

    // Số lượng nhập thêm được dùng chung cho form đang mở trong từng dòng SKU.
    public ?int $adjustmentQuantity = null;

    // Theo nghiệp vụ hiện tại, lý do mặc định của một lần nhập là từ nhà phân phối.
    public string $adjustmentReason = 'Từ nhà phân phối';

    // Khoảng ngày lọc lịch sử movement; để null khi người dùng chưa chọn bộ lọc.
    public ?string $historyStartDate = null;

    public ?string $historyEndDate = null;

    public function mount(): void
    {
        // Mặc định chỉ xem lịch sử trong 7 ngày gần nhất để modal không phải tải quá nhiều dữ liệu.
        $this->historyStartDate = now()->subDays(7)->toDateString();
        $this->historyEndDate = now()->toDateString();
    }

    public function receiveInventory(int $productSkuId): void
    {
        // Validate lại ở server vì dữ liệu từ input Livewire luôn có thể bị thay đổi ngoài giao diện.
        $this->validate([
            'adjustmentQuantity' => ['required', 'integer', 'min:1'],
            'adjustmentReason' => ['required', 'string', 'max:500'],
        ], [], [
            'adjustmentQuantity' => 'số lượng nhập',
            'adjustmentReason' => 'lý do nhập hàng',
        ]);

        abort_unless(auth()->check(), 403);

        // InventoryManager khóa SKU, cập nhật số dư và ghi movement trong cùng một transaction.
        app(InventoryManager::class)->adjust(
            $productSkuId,
            $this->adjustmentQuantity,
            trim($this->adjustmentReason),
            auth()->id(),
        );

        $this->adjustmentQuantity = null;
        $this->adjustmentReason = 'Từ nhà phân phối';

        Notification::make()
            ->title('Đã nhập hàng vào tồn kho')
            ->success()
            ->send();
    }

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
                    ->modalDescription('Theo dõi số lượng tồn kho theo từng biến thể SKU của sản phẩm')
                    ->modalWidth('7xl')
                    // Dùng cùng cửa sổ modal cố định với modal xem Order để nội dung bên trong tự cuộn.
                    ->extraModalWindowAttributes(['class' => 'order-view-modal-window lg'])
                    ->modalContent(function (Product $record) {
                        // Hai khoảng ngày được áp dụng khi modal mở hoặc bộ lọc đổi; icon chỉ hiện dữ liệu đã nạp.
                        $record->load([
                            'skus' => fn ($query) => $query
                                ->withSum([
                                    'inventoryAllocations as allocated_stock' => fn (Builder $allocationQuery): Builder => $allocationQuery
                                        ->where('status', StatusApp::value('inventory_allocation.status', 'allocated')),
                                ], 'quantity')
                                ->with([
                                    'inventoryMovements' => fn ($movementQuery) => $movementQuery
                                        ->when($this->historyStartDate, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
                                        ->when($this->historyEndDate, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
                                        ->with(['order', 'creator'])
                                        ->latest('id'),
                                    'inventoryAllocations' => fn ($allocationQuery) => $allocationQuery
                                        ->where('status', StatusApp::value('inventory_allocation.status', 'allocated'))
                                        ->when($this->historyStartDate, fn ($query, string $date) => $query->whereDate('allocated_at', '>=', $date))
                                        ->when($this->historyEndDate, fn ($query, string $date) => $query->whereDate('allocated_at', '<=', $date))
                                        ->with('order.customer')
                                        ->latest('id'),
                                ])
                                ->orderBy('sku_code'),
                        ]);

                        return view('filament.pages.actions.product-sku-inventory', [
                            'product' => $record,
                            'historyStartDate' => $this->historyStartDate,
                            'historyEndDate' => $this->historyEndDate,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->defaultSort('name');
    }
}
