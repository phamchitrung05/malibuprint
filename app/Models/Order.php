<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

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

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    public function attachments(): MorphMany
    {
        // Order chỉ giữ các liên kết; metadata và trạng thái upload nằm trong ManagedFile.
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    /**
     * Tính lại số tiền từ các dòng sản phẩm thay vì tin vào giá trị hidden từ trình duyệt.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
            ->value('total');
        $discount = min(max(0, (float) $this->discount), $subtotal);

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => max(0, $subtotal - $discount),
        ])->saveQuietly();
    }
}
