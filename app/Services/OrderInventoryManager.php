<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderInventoryAllocation;
use App\Models\ProductSku;
use App\Support\StatusApp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderInventoryManager
{
    public function __construct(private readonly InventoryManager $inventoryManager) {}

    /**
     * Đồng bộ số lượng SKU đã cấp với Order Item hiện tại.
     * Phương thức dùng được cho cả tạo mới và chỉnh sửa vì chỉ áp dụng phần chênh lệch.
     */
    public function syncForOrder(int $orderId, ?int $actorId): void
    {
        DB::transaction(function () use ($orderId, $actorId): void {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $editableStatuses = [
                StatusApp::value('order.status', 'pending'),
                StatusApp::value('order.status', 'processing'),
            ];

            if (! in_array($order->status, $editableStatuses, true)) {
                throw ValidationException::withMessages([
                    'inventory' => 'Chỉ được đồng bộ tồn cho Order mới tạo hoặc đang xử lý.',
                ]);
            }

            /** @var Collection<int, int> $desiredQuantities */
            $desiredQuantities = $order->items()
                ->selectRaw('product_sku_id, SUM(quantity) as total_quantity')
                ->groupBy('product_sku_id')
                ->pluck('total_quantity', 'product_sku_id')
                ->map(fn ($quantity): int => (int) $quantity);

            if ($desiredQuantities->isEmpty()) {
                throw ValidationException::withMessages([
                    'inventory' => 'Order phải có ít nhất một SKU để cấp tồn kho.',
                ]);
            }

            if ($desiredQuantities->contains(fn (int $quantity): bool => $quantity <= 0)) {
                throw ValidationException::withMessages([
                    'inventory' => 'Số lượng của mỗi SKU trong Order phải lớn hơn 0.',
                ]);
            }

            $allocations = OrderInventoryAllocation::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_sku_id');
            $skuIds = $desiredQuantities->keys()
                ->merge($allocations->keys())
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $skus = ProductSku::query()
                ->whereIn('id', $skuIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($skus->count() !== $skuIds->count()) {
                throw ValidationException::withMessages([
                    'inventory' => 'Một SKU trong Order không còn tồn tại.',
                ]);
            }

            foreach ($skuIds as $skuId) {
                $sku = $skus->get($skuId);
                $allocation = $allocations->get($skuId);
                $currentQuantity = (int) ($allocation?->quantity ?? 0);
                $desiredQuantity = (int) $desiredQuantities->get($skuId, 0);
                $difference = $desiredQuantity - $currentQuantity;

                if ($difference === 0) {
                    continue;
                }

                if ($allocation && $allocation->status !== StatusApp::value('inventory_allocation.status', 'allocated')) {
                    throw ValidationException::withMessages([
                        'inventory' => "Phân bổ tồn của SKU {$sku->sku_code} đã khóa và không thể chỉnh sửa.",
                    ]);
                }

                if ($difference > 0 && $sku->status !== StatusApp::value('product_sku.status', 'active')) {
                    throw ValidationException::withMessages([
                        'inventory' => "SKU {$sku->sku_code} đã ngừng bán nên không thể cấp thêm tồn.",
                    ]);
                }

                $allocation ??= OrderInventoryAllocation::query()->create([
                    'order_id' => $order->id,
                    'product_sku_id' => $sku->id,
                    'quantity' => 0,
                    'status' => StatusApp::default('inventory_allocation.status'),
                    'version' => 0,
                    'allocated_at' => now(),
                    'created_by' => $actorId,
                ]);
                $nextVersion = $allocation->version + 1;
                $movementType = match (true) {
                    $currentQuantity === 0 && $difference > 0 => StatusApp::value('inventory_movement.type', 'order_allocated'),
                    $difference > 0 => StatusApp::value('inventory_movement.type', 'order_quantity_increased'),
                    default => StatusApp::value('inventory_movement.type', 'order_quantity_decreased'),
                };

                // Tăng số lượng Order làm giảm tồn; giảm số lượng Order trả phần chênh lệch về tồn.
                $this->inventoryManager->applyToLockedSku(
                    $sku,
                    -$difference,
                    $movementType,
                    "order:{$order->id}:sku:{$sku->id}:allocation-version:{$nextVersion}",
                    order: $order,
                    allocation: $allocation,
                    actorId: $actorId,
                    reason: "Đồng bộ tồn kho cho Order {$order->order_code}",
                    metadata: [
                        'previous_quantity' => $currentQuantity,
                        'new_quantity' => $desiredQuantity,
                    ],
                );

                $allocation->forceFill([
                    'quantity' => $desiredQuantity,
                    'version' => $nextVersion,
                    'allocated_at' => $allocation->allocated_at ?? now(),
                ])->save();
            }
        });
    }

    /**
     * Hủy Order mới tạo hoặc đang xử lý sẽ hoàn đúng lượng đang allocated và khóa allocation ở released.
     */
    public function releaseForCancelledOrder(Order $order, ?int $actorId): void
    {
        $allocations = OrderInventoryAllocation::query()
            ->where('order_id', $order->id)
            ->where('status', StatusApp::value('inventory_allocation.status', 'allocated'))
            ->lockForUpdate()
            ->get();
        $skus = ProductSku::query()
            ->whereIn('id', $allocations->pluck('product_sku_id')->sort()->values())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            $sku = $skus->get($allocation->product_sku_id);
            $nextVersion = $allocation->version + 1;

            if ($allocation->quantity > 0) {
                $this->inventoryManager->applyToLockedSku(
                    $sku,
                    $allocation->quantity,
                    StatusApp::value('inventory_movement.type', 'order_cancelled_restore'),
                    "order:{$order->id}:sku:{$sku->id}:cancel:allocation-version:{$nextVersion}",
                    order: $order,
                    allocation: $allocation,
                    actorId: $actorId,
                    reason: "Hoàn tồn do hủy Order {$order->order_code}",
                );
            }

            $allocation->forceFill([
                'status' => StatusApp::value('inventory_allocation.status', 'released'),
                'version' => $nextVersion,
                'released_at' => now(),
            ])->save();
        }
    }

    /**
     * Hoàn thành sản xuất chỉ đánh dấu lượng đã cấp là consumed vì tồn đã được trừ lúc tạo Order.
     */
    public function consumeForCompletedOrder(Order $order): void
    {
        OrderInventoryAllocation::query()
            ->where('order_id', $order->id)
            ->where('status', StatusApp::value('inventory_allocation.status', 'allocated'))
            ->lockForUpdate()
            ->update([
                'status' => StatusApp::value('inventory_allocation.status', 'consumed'),
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
