<?php

namespace App\Enums;

use App\Support\StatusApp;

/** Phương thức vận chuyển chỉ áp dụng cho Order giao một lần. */
enum ShippingMethod: string
{
    case Standard = 'standard';
    case BestExpress = 'best_express';

    public function label(): string
    {
        return StatusApp::label('order.shipping_method', $this->value);
    }
}
