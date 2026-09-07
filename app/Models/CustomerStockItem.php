<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerStockItem extends Model
{
    // Dòng tồn giữ nguồn gốc Order Item để đơn giá và SKU luôn truy vết được.
    protected $fillable = [
        'customer_stock_id',
        'order_item_id',
        'received_quantity',
        'released_quantity',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'integer',
            'released_quantity' => 'integer',
        ];
    }

    public function customerStock(): BelongsTo
    {
        return $this->belongsTo(CustomerStock::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function releaseItems(): HasMany
    {
        return $this->hasMany(StockReleaseItem::class);
    }

    /**
     * Số còn lại luôn được tính từ số nhập và số đã xuất để chỉ có một nguồn dữ liệu đáng tin cậy.
     */
    public function remainingQuantity(): int
    {
        return max(0, $this->received_quantity - $this->released_quantity);
    }
}
