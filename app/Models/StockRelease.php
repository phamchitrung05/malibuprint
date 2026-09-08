<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StockRelease extends Model
{
    // Phiếu xuất là chứng từ giao hàng; trạng thái thu tiền được suy ra từ quan hệ payment.
    protected $fillable = [
        'uuid',
        'release_code',
        'customer_stock_id',
        'released_at',
        'gross_product_amount',
        'gross_service_amount',
        'allocated_discount',
        'reconciliation_adjustment',
        'total_amount',
        'allocated_shipping_fee',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'gross_product_amount' => 'decimal:2',
            'gross_service_amount' => 'decimal:2',
            'allocated_discount' => 'decimal:2',
            'reconciliation_adjustment' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'allocated_shipping_fee' => 'decimal:2',
        ];
    }

    public function customerStock(): BelongsTo
    {
        return $this->belongsTo(CustomerStock::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReleaseItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(Shipping::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
