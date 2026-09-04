<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSequence extends Model
{
    protected $table = 'order_sequences';

    protected $fillable = ['sequence_date', 'last_number'];

    protected function casts(): array
    {
        // Ngày sequence được cast để truy vấn và tạo mã luôn dùng cùng định dạng.
        return ['sequence_date' => 'date', 'last_number' => 'integer'];
    }
}
