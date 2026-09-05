<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderActivityLogger;

class OrderObserver
{
    public function __construct(private readonly OrderActivityLogger $logger) {}

    public function created(Order $order): void
    {
        $this->logger->log($order, 'order.created', 'Đã tạo đơn hàng');
    }

    public function updated(Order $order): void
    {
        $changes = collect($order->getChanges())->except(['updated_at', 'is_paid', 'is_delivered'])->all();

        if ($changes === []) {
            return;
        }

        $oldValues = collect(array_keys($changes))
            ->mapWithKeys(fn (string $key): array => [$key => $order->getRawOriginal($key)])
            ->all();
        $event = array_key_exists('status', $changes) ? 'order.status_changed' : 'order.updated';
        $description = match ($changes['status'] ?? null) {
            'processing' => 'Đã bắt đầu xử lý đơn hàng',
            'completed' => 'Đã hoàn thành sản xuất',
            'cancelled' => 'Đã hủy đơn hàng',
            default => 'Đã chỉnh sửa đơn hàng',
        };

        $this->logger->log($order, $event, $description, [
            'old' => $oldValues,
            'new' => $changes,
        ]);
    }
}
