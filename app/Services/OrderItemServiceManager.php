<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItemService;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class OrderItemServiceManager
{
    /**
     * Đồng bộ lựa chọn dịch vụ từ form sau khi Filament đã lưu xong các Order Item.
     *
     * @param  array<int|string, array<string, mixed>>  $itemStates
     */
    public function syncForOrder(int $orderId, array $itemStates): void
    {
        DB::transaction(function () use ($orderId, $itemStates): void {
            $order = Order::query()
                ->with(['items.productSku.product', 'items.services'])
                ->lockForUpdate()
                ->findOrFail($orderId);
            $service = Service::query()
                ->where('code', Service::CUP_PRINTING_CODE)
                ->firstOrFail();
            $selectedSkuIds = collect($itemStates)
                ->filter(fn (array $item): bool => (bool) ($item['include_cup_printing_service'] ?? false))
                ->pluck('product_sku_id')
                ->filter()
                ->mapWithKeys(fn ($skuId): array => [(int) $skuId => true]);

            foreach ($order->items as $item) {
                /** @var OrderItemService|null $snapshot */
                $snapshot = $item->services->firstWhere('service_id', $service->id);
                $supportsService = $item->productSku?->product?->product_type === $service->product_type;
                $shouldAttach = $supportsService && $selectedSkuIds->has($item->product_sku_id);

                if (! $shouldAttach) {
                    $snapshot?->delete();

                    continue;
                }

                // Dịch vụ ngừng hoạt động không được gắn mới, nhưng snapshot cũ vẫn được phép cập nhật số lượng.
                if (! $service->is_active && $snapshot === null) {
                    continue;
                }

                $snapshot ??= new OrderItemService([
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'unit_price' => $service->unit_price,
                ]);
                $unitPrice = (float) $snapshot->unit_price;

                $snapshot->fill([
                    // Mỗi toggle là một lượt; chi phí của lượt đó nhân theo số lượng Product trên Order Item.
                    'quantity' => $item->quantity,
                    'subtotal' => round($unitPrice * $item->quantity, 2),
                ]);
                $item->services()->save($snapshot);
            }
        });
    }
}
