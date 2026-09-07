<?php

namespace App\Enums;

use App\Support\StatusApp;

/** Hình thức giao thành phẩm được chốt khi tạo Order. */
enum FulfillmentMode: string
{
    case Single = 'single';
    case CustomerStock = 'customer_stock';

    public function label(): string
    {
        return StatusApp::label('order.fulfillment_mode', $this->value);
    }
}
