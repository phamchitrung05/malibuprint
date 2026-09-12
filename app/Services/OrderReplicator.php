<?php

namespace App\Services;

use App\Models\Order;
use App\Support\StatusApp;
use Illuminate\Support\Facades\DB;

class OrderReplicator
{
    public function __construct(
        private readonly OrderCodeService $orderCodeService,
        private readonly OrderInventoryManager $orderInventoryManager,
    ) {}

    /**
     * Tạo một Order mới từ nội dung đã chốt của Order nguồn, không sao chép lifecycle và chứng từ cũ.
     */
    public function replicate(Order $sourceOrder, ?int $actorId): Order
    {
        return DB::transaction(function () use ($sourceOrder, $actorId): Order {
            $sourceOrder = Order::query()
                ->with(['items.services'])
                ->lockForUpdate()
                ->findOrFail($sourceOrder->id);
            $orderDate = now();
            $deliveryDate = null;

            if ($sourceOrder->delivery_date !== null) {
                $leadDays = $sourceOrder->order_date === null
                    ? 0
                    : max(0, (int) $sourceOrder->order_date->startOfDay()->diffInDays($sourceOrder->delivery_date, false));
                $deliveryDate = $orderDate->copy()->startOfDay()->addDays($leadDays);
            }

            $order = Order::query()->create([
                'order_code' => $this->orderCodeService->generate($orderDate),
                'customer_id' => $sourceOrder->customer_id,
                'order_date' => $orderDate,
                'delivery_date' => $deliveryDate,
                'status' => StatusApp::default('order.status'),
                'fulfillment_mode' => $sourceOrder->fulfillment_mode,
                'fulfillment_status' => StatusApp::default('order.fulfillment_status'),
                'closed_at' => null,
                'subtotal' => 0,
                'discount' => $sourceOrder->discount,
                'shipping_fee' => $sourceOrder->shipping_fee,
                'shipping_method' => StatusApp::default('order.shipping_method'),
                'shipping_tracking_code' => null,
                'total_amount' => 0,
                'note' => $sourceOrder->note,
                'created_by' => $actorId,
                'is_delivered' => false,
                'is_paid' => false,
            ]);

            foreach ($sourceOrder->items as $sourceItem) {
                $item = $order->items()->create([
                    'product_sku_id' => $sourceItem->product_sku_id,
                    'quantity' => $sourceItem->quantity,
                    'unit_price' => $sourceItem->unit_price,
                    'subtotal' => $sourceItem->subtotal,
                ]);

                foreach ($sourceItem->services as $sourceService) {
                    $item->services()->create([
                        'service_id' => $sourceService->service_id,
                        'service_name' => $sourceService->service_name,
                        'quantity' => $sourceService->quantity,
                        'unit_price' => $sourceService->unit_price,
                        'subtotal' => $sourceService->subtotal,
                    ]);
                }
            }

            $order->recalculateTotals();
            $this->orderInventoryManager->syncForOrder($order->id, $actorId);

            return $order->refresh();
        });
    }
}
