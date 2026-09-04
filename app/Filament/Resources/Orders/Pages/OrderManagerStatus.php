<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;

class OrderManagerStatus extends Page implements HasTable
{
    use InteractsWithTable;

    // ID của dòng đang chọn; Livewire sẽ tự render lại panel chi tiết khi giá trị thay đổi.
    public ?int $selectedOrderId = null;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.pages.order-manager-status-page';

    // Page quản lý trạng thái đơn hàng, hiện ở sub-navigation của OrderResource.
    protected static ?string $navigationLabel = 'Trạng thái đơn hàng';

    protected static ?string $title = 'Trạng thái đơn hàng';

    public function mount(): void
    {
        // Chọn sẵn đơn đang xử lý gần nhất; nếu không có thì dùng đơn mới nhất.
        $this->selectedOrderId = Order::query()
            ->where('status', 'processing')
            ->latest('order_date')
            ->value('id') ?? Order::query()->latest('order_date')->value('id');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getOrdersQuery())
            ->columns($this->getTableColumns())
            ->defaultPaginationPageOption(10)
            // Cho phép click bất kỳ vị trí nào trên dòng để đổi panel chi tiết bên phải.
            ->recordAction('selectOrder')
            ->toolbarActions($this->getTableToolbarActions());
    }

    /**
     * Phát ID sang component chi tiết mà không render lại Page chứa Filament Table.
     */
    #[Renderless]
    public function selectOrder(int|string $record): void
    {
        // Filament gửi khóa của dòng, không gửi trực tiếp model vào Livewire method.
        $this->selectedOrderId = (int) $record;
        $this->dispatch('order-selected', orderId: $this->selectedOrderId);
    }

    /**
     * Chỉ render lại bảng khi component chi tiết vừa tạo thêm một Order mới.
     */
    #[On('order-list-refresh')]
    public function refreshOrderTable(): void {}

    /**
     * Query dùng chung cho bảng để mọi quan hệ hiển thị được eager load một lần.
     */
    private function getOrdersQuery(): Builder
    {
        return Order::query()
            // Bảng chỉ lấy những cột và trường quan hệ thực sự được hiển thị.
            ->select(['id', 'order_code', 'customer_id', 'order_date', 'total_amount'])
            ->with([
                'customer:id,name,address',
                'items:id,order_id,product_sku_id,quantity',
                'items.productSku:id,product_id',
                'items.productSku.product:id,name',
            ])
            ->latest('order_date');
    }

    /**
     * Mỗi cột chỉ mô tả cách trình bày; dữ liệu quan hệ đã được nạp bởi getOrdersQuery().
     *
     * @return array<int, TextColumn>
     */
    private function getTableColumns(): array
    {
        return [
            TextColumn::make('order_code')
                ->label('Mã đơn')
                ->prefix('#')
                ->weight('bold')
                ->color('primary')
                ->searchable(),
            TextColumn::make('customer.name')
                ->label('Khách hàng')
                ->description(fn (Order $record): ?string => $record->customer?->address)
                ->searchable(),
            TextColumn::make('product_name')
                ->label('Sản phẩm')
                ->getStateUsing(fn (Order $record): string => $record->items->first()?->productSku?->product?->name ?? 'Chưa có sản phẩm')
                ->limit(18),
            TextColumn::make('quantity')
                ->label('SL')
                ->getStateUsing(fn (Order $record): int => (int) ($record->items->first()?->quantity ?? 0)),
            TextColumn::make('total_amount')
                ->label('Tổng tiền')
                ->money('VND')
                ->sortable(),
            TextColumn::make('order_date')
                ->label('Ngày tạo')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ];
    }

    /** @return array<int, BulkActionGroup> */
    private function getTableToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
