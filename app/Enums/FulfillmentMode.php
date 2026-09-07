<?php

namespace App\Enums;

/** Hình thức giao thành phẩm được chốt khi tạo Order. */
enum FulfillmentMode: string
{
    case Single = 'single';
    case CustomerStock = 'customer_stock';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Giao hàng một lần',
            self::CustomerStock => 'Lưu kho và xuất nhiều đợt',
        };
    }
}
