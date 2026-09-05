<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'customers';
    protected $fillable = ['uuid', 'name', 'phone', 'address', 'note', 'is_active', 'last_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_order' => 'datetime'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(CustomerStock::class);
    }
}
