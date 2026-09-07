<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Models\ProductSku;
use App\Support\StatusApp;

class ProductSkuObserver
{
    /**
     * SKU mới có thể khai báo tồn đầu kỳ; observer chỉ ghi sổ vì số dư đã được lưu cùng record SKU.
     */
    public function created(ProductSku $productSku): void
    {
        $openingBalance = max(0, (int) $productSku->stock);

        if ($openingBalance === 0) {
            return;
        }

        InventoryMovement::query()->firstOrCreate(
            ['idempotency_key' => "opening-balance:product-sku:{$productSku->id}"],
            [
                'product_sku_id' => $productSku->id,
                'type' => StatusApp::value('inventory_movement.type', 'opening_balance'),
                'quantity' => $openingBalance,
                'balance_before' => 0,
                'balance_after' => $openingBalance,
                'reason' => 'Ghi nhận tồn đầu kỳ khi tạo SKU',
                'created_by' => auth()->id(),
            ],
        );
    }
}
