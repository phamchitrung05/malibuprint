<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderInventoryAllocation;
use App\Models\ProductSku;
use App\Services\OrderInventoryManager;
use App\Support\StatusApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeActiveOrderInventory extends Command
{
    protected $signature = 'inventory:initialize-active-orders {--apply : Thực sự tạo allocation và trừ tồn}';

    protected $description = 'Kiểm tra và khởi tạo phân bổ tồn cho các Order pending/processing có trước tính năng inventory';

    public function handle(OrderInventoryManager $orderInventoryManager): int
    {
        $activeStatuses = [
            StatusApp::value('order.status', 'pending'),
            StatusApp::value('order.status', 'processing'),
        ];
        $requiredBySku = DB::table('order_item')
            ->join('orders', 'orders.id', '=', 'order_item.order_id')
            ->whereIn('orders.status', $activeStatuses)
            ->selectRaw('order_item.product_sku_id, SUM(order_item.quantity) as quantity')
            ->groupBy('order_item.product_sku_id')
            ->pluck('quantity', 'product_sku_id');
        $allocatedBySku = OrderInventoryAllocation::query()
            ->where('status', StatusApp::value('inventory_allocation.status', 'allocated'))
            ->selectRaw('product_sku_id, SUM(quantity) as quantity')
            ->groupBy('product_sku_id')
            ->pluck('quantity', 'product_sku_id');
        $skus = ProductSku::query()
            ->with('product')
            ->whereIn('id', $requiredBySku->keys())
            ->orderBy('id')
            ->get();
        $rows = $skus->map(function (ProductSku $sku) use ($requiredBySku, $allocatedBySku): array {
            $missingAllocation = max(
                0,
                (int) $requiredBySku->get($sku->id, 0) - (int) $allocatedBySku->get($sku->id, 0),
            );

            return [
                $sku->sku_code,
                $sku->product?->name,
                $sku->stock,
                $missingAllocation,
                (int) $sku->stock - $missingAllocation,
            ];
        });

        $this->table(
            ['SKU', 'Sản phẩm', 'Tồn hiện tại', 'Cần cấp bổ sung', 'Tồn dự kiến'],
            $rows,
        );

        if ($rows->contains(fn (array $row): bool => $row[4] < 0)) {
            $this->error('Không thể khởi tạo vì có SKU không đủ tồn. Hãy điều chỉnh tồn trước khi chạy lại.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->info('Đây là chế độ kiểm tra. Dùng --apply để thực sự cập nhật tồn kho.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($activeStatuses, $orderInventoryManager): void {
            Order::query()
                ->whereIn('status', $activeStatuses)
                ->whereHas('items')
                ->orderBy('id')
                ->eachById(function (Order $order) use ($orderInventoryManager): void {
                    $orderInventoryManager->syncForOrder($order->id, auth()->id());
                });
        });

        $this->info('Đã khởi tạo phân bổ tồn cho toàn bộ Order đang hoạt động.');

        return self::SUCCESS;
    }
}
