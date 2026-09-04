<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['order_code', 'customer_id', 'order_date', 'status', 'subtotal', 'discount', 'total_amount', 'note', 'created_by', 'is_delivered', 'is_paid'];

    protected function casts(): array
    {
        // Ngày và số tiền được ép kiểu để form và phép tính đơn hàng hoạt động chính xác.
        return ['order_date' => 'datetime', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total_amount' => 'decimal:2', 'is_delivered' => 'boolean', 'is_paid' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipping(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }
}
