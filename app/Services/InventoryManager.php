<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderInventoryAllocation;
use App\Models\ProductSku;
use App\Support\StatusApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryManager
{
    /**
     * Áp dụng một thay đổi lên SKU đã được lock trong transaction của nghiệp vụ gọi đến.
     * Movement và số dư luôn được ghi cùng nhau để không tồn tại lịch sử thiếu hoặc số dư mồ côi.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function applyToLockedSku(
        ProductSku $sku,
        int $quantity,
        string $type,
        string $idempotencyKey,
        ?Order $order = null,
        ?OrderInventoryAllocation $allocation = null,
        ?int $actorId = null,
        ?string $reason = null,
        array $metadata = [],
    ): InventoryMovement {
        $existingMovement = InventoryMovement::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingMovement) {
            return $existingMovement;
        }

        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'inventory' => 'Số lượng thay đổi tồn kho phải khác 0.',
            ]);
        }

        $balanceBefore = max(0, (int) $sku->stock);
        $balanceAfter = $balanceBefore + $quantity;

        if ($balanceAfter < 0) {
            throw ValidationException::withMessages([
                'inventory' => sprintf(
                    'SKU %s chỉ còn %s sản phẩm, không đủ để cấp thêm %s sản phẩm.',
                    $sku->sku_code,
                    number_format($balanceBefore),
                    number_format(abs($quantity)),
                ),
            ]);
        }

        $sku->forceFill(['stock' => $balanceAfter])->saveQuietly();

        return InventoryMovement::query()->create([
            'product_sku_id' => $sku->id,
            'order_id' => $order?->id,
            'allocation_id' => $allocation?->id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'idempotency_key' => $idempotencyKey,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_by' => $actorId,
        ]);
    }

    /**
     * Điều chỉnh thủ công luôn lock SKU và ghi lý do để số dư có thể đối soát về sau.
     */
    public function adjust(int $productSkuId, int $quantity, string $reason, ?int $actorId): InventoryMovement
    {
        return DB::transaction(function () use ($productSkuId, $quantity, $reason, $actorId): InventoryMovement {
            if ($quantity === 0 || blank($reason)) {
                throw ValidationException::withMessages([
                    'inventory' => 'Hãy nhập số lượng khác 0 và lý do điều chỉnh tồn kho.',
                ]);
            }

            $sku = ProductSku::query()->lockForUpdate()->findOrFail($productSkuId);
            $type = $quantity > 0
                ? StatusApp::value('inventory_movement.type', 'manual_increase')
                : StatusApp::value('inventory_movement.type', 'manual_decrease');

            return $this->applyToLockedSku(
                $sku,
                $quantity,
                $type,
                'manual-adjustment:'.Str::uuid(),
                actorId: $actorId,
                reason: trim($reason),
            );
        });
    }
}
