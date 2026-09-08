<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReleaseItemService extends Model
{
    protected $fillable = [
        'stock_release_item_id',
        'order_item_service_id',
        'service_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function stockReleaseItem(): BelongsTo
    {
        return $this->belongsTo(StockReleaseItem::class);
    }

    public function orderItemService(): BelongsTo
    {
        return $this->belongsTo(OrderItemService::class);
    }
}
