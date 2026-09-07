<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSku extends Model
{
    protected $table = 'product_sku';

    // Bảng SKU chỉ có created_at nên phải tắt tự động ghi updated_at của Eloquent.
    public $timestamps = false;

    protected $fillable = ['product_id', 'sku_code', 'price', 'stock', 'status'];

    protected function casts(): array
    {
        // Giá dùng decimal để không làm mất phần thập phân khi hiển thị và lưu trữ.
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_sku_id');
    }

    public function inventoryAllocations(): HasMany
    {
        return $this->hasMany(OrderInventoryAllocation::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function customerStocks(): HasMany
    {
        return $this->hasMany(CustomerStock::class, 'product_sku_id');
    }
}
