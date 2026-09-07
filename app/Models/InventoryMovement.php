<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_sku_id',
        'order_id',
        'allocation_id',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'idempotency_key',
        'reason',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Movement là sổ đối soát bất biến; sai sót phải được sửa bằng một movement bù trừ mới.
        static::updating(fn (): never => throw new LogicException('Không được chỉnh sửa giao dịch tồn kho.'));
        static::deleting(fn (): never => throw new LogicException('Không được xóa giao dịch tồn kho.'));
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(OrderInventoryAllocation::class, 'allocation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
