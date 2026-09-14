<?php

namespace App\Enums;

use App\Support\StatusApp;

/** Phương thức vận chuyển được chọn trên Order và dùng chung cho các lần giao. */
enum ShippingMethod: string
{
    case Express = 'express';
    case Vehicle = 'vehicle';

    // Giữ tên cũ cho mã nghiệp vụ và test hiện hữu; giá trị lưu mới vẫn là express/vehicle.
    public const BestExpress = self::Express;

    public const Standard = self::Vehicle;

    public function label(): string
    {
        return StatusApp::label('order.shipping_method', $this->value);
    }
}
