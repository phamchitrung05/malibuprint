<?php

namespace App\Services;

use App\Models\Order;

class OrderActivityLogger
{
    /**
     * Ghi một sự kiện nghiệp vụ vào timeline chung của Order.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(Order $order, string $event, string $description, array $properties = []): void
    {
        $activity = activity('orders')
            ->performedOn($order)
            ->event($event)
            ->withProperties($properties);

        if ($user = auth()->user()) {
            $activity->causedBy($user);
        }

        $activity->log($description);
    }
}
