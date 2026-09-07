<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Support\StatusApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerStockManager
{
    public function __construct(private readonly OrderActivityLogger $activityLogger) {}

    /**
     * Nhập toàn bộ thành phẩm của Order vào một lô kho duy nhất.
     *
     * Order được khóa và order_id là duy nhất nên thao tác có thể gọi lại an toàn mà không nhân đôi tồn kho.
     */
    public function createForCompletedOrder(int $orderId, ?int $actorId): ?CustomerStock
    {
        return DB::transaction(function () use ($orderId, $actorId): ?CustomerStock {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->fulfillment_mode !== FulfillmentMode::CustomerStock) {
                return null;
            }

            if ($order->status !== StatusApp::value('order.status', 'completed')) {
                throw ValidationException::withMessages([
                    'order' => 'Chỉ được nhập kho khi đơn hàng đã hoàn thành sản xuất.',
                ]);
            }

            $existingStock = CustomerStock::query()->where('order_id', $order->id)->first();

            if ($existingStock) {
                return $existingStock;
            }

            $orderItems = $order->items()->get();

            if ($orderItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'Đơn hàng phải có ít nhất một sản phẩm trước khi nhập kho.',
                ]);
            }

            $customerStock = CustomerStock::query()->create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'stocked_at' => now(),
                'created_by' => $actorId,
            ]);

            foreach ($orderItems as $orderItem) {
                $customerStock->items()->create([
                    'order_item_id' => $orderItem->id,
                    'received_quantity' => $orderItem->quantity,
                    'released_quantity' => 0,
                ]);
            }

            $order->forceFill([
                'fulfillment_status' => FulfillmentStatus::Ready,
            ])->saveQuietly();

            $this->activityLogger->log($order, 'customer_stock.created', 'Đã nhập thành phẩm vào kho khách hàng', [
                'customer_stock_id' => $customerStock->id,
                'item_count' => $orderItems->count(),
                'total_quantity' => $orderItems->sum('quantity'),
            ]);

            return $customerStock;
        });
    }
}
