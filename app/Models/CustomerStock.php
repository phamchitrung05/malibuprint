<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerStock extends Model
{
    protected $table = 'customer_stock';

    protected $fillable = [
        'customer_id',
        'order_id',
        'note',
        'stocked_at',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stocked_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerStockItem::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(StockRelease::class)->latest('released_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
