<?php

namespace App\Observers;

use App\Models\Shipping;
use App\Services\OrderActivityLogger;

class ShippingObserver
{
    public function __construct(private readonly OrderActivityLogger $logger) {}

    public function created(Shipping $shipping): void
    {
        $this->logger->log($shipping->order, 'shipping.created', 'Đã xác nhận giao hàng', [
            'shipping_id' => $shipping->id,
            'status' => $shipping->status,
            'delivered_at' => $shipping->delivered_at?->toDateTimeString(),
        ]);
    }
}
