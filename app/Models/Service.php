<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    public const CUP_PRINTING_CODE = 'in_ly';

    protected $fillable = ['code', 'name', 'unit_price', 'product_type', 'is_active'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function orderItemServices(): HasMany
    {
        return $this->hasMany(OrderItemService::class);
    }
}
