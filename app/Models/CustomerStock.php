<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CustomerStock extends Model
{
    protected $table = 'customer_stock';
    protected $fillable = ['customer_id', 'product_sku_id', 'quantity', 'note'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function productSku(): BelongsTo { return $this->belongsTo(ProductSku::class); }
}
