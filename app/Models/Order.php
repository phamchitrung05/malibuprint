<?php

namespace App\Models;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Support\StatusApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'customer_id',
        'order_date',
        'delivery_date',
        'status',
        'fulfillment_mode',
        'fulfillment_status',
        'closed_at',
        'subtotal',
        'discount',
        'shipping_fee',
        'total_amount',
        'note',
        'created_by',
        'is_delivered',
        'is_paid',
    ];

    protected function casts(): array
    {
        // Ngày và số tiền được ép kiểu để form và phép tính đơn hàng hoạt động chính xác.
        return [
            'order_date' => 'datetime',
            'delivery_date' => 'date',
            'fulfillment_mode' => FulfillmentMode::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'closed_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_delivered' => 'boolean',
            'is_paid' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            // Model dùng cùng default với Form và Service thay vì lặp chuỗi trạng thái rải rác.
            $order->status ??= StatusApp::default('order.status');
            $order->fulfillment_mode ??= StatusApp::default('order.fulfillment_mode');
            $order->fulfillment_status ??= StatusApp::default('order.fulfillment_status');
        });
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

    public function inventoryAllocations(): HasMany
    {
        return $this->hasMany(OrderInventoryAllocation::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function shipping(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }

    public function customerStock(): HasOne
    {
        return $this->hasOne(CustomerStock::class);
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
        $shippingFee = max(0, (float) $this->shipping_fee);

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shippingFee,
            'total_amount' => $subtotal - $discount + $shippingFee,
        ])->saveQuietly();
    }
}
