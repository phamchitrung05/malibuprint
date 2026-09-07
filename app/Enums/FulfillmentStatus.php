<?php

namespace App\Enums;

use App\Support\StatusApp;

/** Tiến độ giao hàng độc lập với tiến độ sản xuất trong orders.status. */
enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case PartiallyReleased = 'partially_released';
    case FullyReleased = 'fully_released';

    public function label(): string
    {
        return StatusApp::label('order.fulfillment_status', $this->value);
    }
}
