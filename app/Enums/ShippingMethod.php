<?php

namespace App\Enums;

use App\Support\StatusApp;

/** Phương thức vận chuyển được chọn trên Order và dùng chung cho các lần giao. */
enum ShippingMethod: string
{
    case Express = 'express';
    case Vehicle = 'vehicle';
    case CustomerPickup = 'customer_pickup';
    case InnerCity = 'inner_city';

    public function label(): string
    {
        return StatusApp::label('order.shipping_method', $this->value);
    }
}
