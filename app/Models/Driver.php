<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    // Tắt is_active thay vì xóa để các Order cũ vẫn giữ được dữ liệu liên kết.
    protected $fillable = [
        'name',
        'phone',
        'license_plate',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
