<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payment';

    protected $fillable = [
        'order_id',
        'stock_release_id',
        'payment_date',
        'amount',
        'status',
        'note',
        'confirmed_by',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['payment_date' => 'datetime', 'amount' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function stockRelease(): BelongsTo
    {
        return $this->belongsTo(StockRelease::class);
    }
}
