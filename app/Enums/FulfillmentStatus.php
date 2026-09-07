<?php

namespace App\Enums;

/** Tiến độ giao hàng độc lập với tiến độ sản xuất trong orders.status. */
enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case PartiallyReleased = 'partially_released';
    case FullyReleased = 'fully_released';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa sẵn sàng giao',
            self::Ready => 'Lưu kho, sẵn sàng giao',
            self::PartiallyReleased => 'Đã xuất một phần',
            self::FullyReleased => 'Đã xuất hết',
        };
    }
}
