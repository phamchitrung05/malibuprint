<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    // Migration dùng tên bảng số ít nên cần khai báo tường minh để Eloquent không tự đoán thành "products".
    protected $table = 'product';

    // Danh sách thuộc tính được phép nhận dữ liệu từ form quản trị Filament.
    protected $fillable = ['name', 'product_type', 'unit', 'is_active', 'note', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        // Ép kiểu trạng thái về boolean để checkbox Filament và dữ liệu database nhất quán.
        return ['is_active' => 'boolean'];
    }

    // Một sản phẩm gốc có thể có nhiều biến thể SKU.
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    public function inventoryAllocations(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderInventoryAllocation::class,
            ProductSku::class,
            'product_id',
            'product_sku_id',
        );
    }
}
