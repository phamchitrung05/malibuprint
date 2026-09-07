<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReleaseItem extends Model
{
    // Đơn giá và thành tiền được snapshot để lịch sử phiếu không đổi theo dữ liệu Order sau này.
    protected $fillable = [
        'stock_release_id',
        'customer_stock_item_id',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function stockRelease(): BelongsTo
    {
        return $this->belongsTo(StockRelease::class);
    }

    public function customerStockItem(): BelongsTo
    {
        return $this->belongsTo(CustomerStockItem::class);
    }
}
