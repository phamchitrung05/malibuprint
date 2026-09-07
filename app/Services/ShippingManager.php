<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\Shipping;
use App\Support\StatusApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShippingManager
{
    public function __construct(private readonly OrderActivityLogger $activityLogger) {}

    /**
     * Xác nhận giao toàn bộ Order single và tái sử dụng chứng từ pending nếu dữ liệu cũ đã tạo trước.
     */
    public function confirmSingleOrder(int $orderId, int $actorId): Shipping
    {
        return DB::transaction(function () use ($orderId, $actorId): Shipping {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== StatusApp::value('order.status', 'completed')
                || $order->fulfillment_mode !== FulfillmentMode::Single) {
                throw ValidationException::withMessages([
                    'shipping' => 'Chỉ được giao toàn bộ cho Order single đã hoàn thành sản xuất.',
                ]);
            }

            if ($order->is_delivered) {
                throw ValidationException::withMessages([
                    'shipping' => 'Order này đã được xác nhận giao hàng.',
                ]);
            }

            $shippingData = [
                'status' => StatusApp::value('shipping.status', 'delivered'),
                'shipped_at' => now(),
                'delivered_at' => now(),
                'confirmed_by' => $actorId,
            ];
            $shipping = $order->shipping()
                ->where('status', StatusApp::value('shipping.status', 'pending'))
                ->lockForUpdate()
                ->first();

            if ($shipping) {
                $shipping->forceFill($shippingData)->saveQuietly();

                $this->activityLogger->log($order, 'shipping.confirmed', 'Đã xác nhận giao hàng chờ', [
                    'shipping_id' => $shipping->id,
                    'status' => $shipping->status,
                ]);
            } else {
                $shipping = $order->shipping()->create($shippingData);
            }

            $order->forceFill([
                'is_delivered' => true,
                'fulfillment_status' => FulfillmentStatus::FullyReleased,
            ])->saveQuietly();

            app(OrderClosureManager::class)->closeIfReady($order->fresh());

            return $shipping;
        });
    }
}
