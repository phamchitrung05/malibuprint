<?php

namespace App\Services;

use App\Models\Order;

class OrderClosureManager
{
    public function __construct(private readonly OrderActivityLogger $activityLogger) {}

    /**
     * Ghi nhận mốc kết thúc đúng một lần khi sản xuất, giao hàng và thanh toán đã hoàn tất.
     *
     * Phương thức được gọi trong transaction của nghiệp vụ cuối cùng, vì vậy closed_at và activity
     * luôn cùng thành công hoặc cùng rollback.
     */
    public function closeIfReady(Order $order): bool
    {
        if ($order->closed_at !== null
            || $order->status !== 'completed'
            || ! $order->is_delivered
            || ! $order->is_paid) {
            return false;
        }

        $order->forceFill(['closed_at' => now()])->saveQuietly();

        // Chỉ ghi nhận ngày tạo của đơn đã kết thúc; đơn cũ hoàn thành muộn không được ghi đè đơn mới hơn.
        $customer = $order->customer()->firstOrFail();
        if ($customer->last_order === null || $customer->last_order->lt($order->order_date)) {
            $customer->forceFill(['last_order' => $order->order_date])->saveQuietly();
        }

        $this->activityLogger->log($order, 'order.closed', 'Đã kết thúc đơn hàng', [
            'closed_at' => $order->closed_at?->toDateTimeString(),
        ]);

        return true;
    }
}
