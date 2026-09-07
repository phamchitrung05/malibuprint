<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderInventoryAllocation extends Model
{
    protected $fillable = [
        'order_id',
        'product_sku_id',
        'quantity',
        'status',
        'version',
        'allocated_at',
        'consumed_at',
        'released_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'version' => 'integer',
            'allocated_at' => 'datetime',
            'consumed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'allocation_id');
    }
}
