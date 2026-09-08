<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Models\CustomerStock;
use App\Models\CustomerStockItem;
use App\Models\Order;
use App\Models\StockRelease;
use App\Support\StatusApp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockReleaseManager
{
    public function __construct(private readonly OrderActivityLogger $activityLogger) {}

    /**
     * Tạo một phiếu xuất, trừ tồn và xác nhận Shipping trong cùng transaction.
     *
     * @param  array<int|string, int|string|null>  $quantities  Số lượng xuất, được đánh key bằng customer_stock_item_id.
     */
    public function release(int $customerStockId, array $quantities, ?string $note, int $actorId): StockRelease
    {
        return DB::transaction(function () use ($customerStockId, $quantities, $note, $actorId): StockRelease {
            $customerStock = CustomerStock::query()->lockForUpdate()->findOrFail($customerStockId);
            $order = Order::query()->lockForUpdate()->findOrFail($customerStock->order_id);

            $this->ensureOrderCanRelease($order);

            // Service tự validate để mọi entry point tương lai (API, command, Filament) đều giữ cùng invariant.
            Validator::make(['quantities' => $quantities], [
                'quantities' => ['required', 'array'],
                'quantities.*' => ['nullable', 'integer', 'min:0'],
            ])->validate();

            $requestedQuantities = collect($quantities)
                ->mapWithKeys(fn ($quantity, $itemId): array => [(int) $itemId => (int) $quantity])
                ->filter(fn (int $quantity): bool => $quantity > 0);

            if ($requestedQuantities->isEmpty()) {
                throw ValidationException::withMessages([
                    'quantities' => 'Hãy nhập số lượng cần xuất cho ít nhất một sản phẩm.',
                ]);
            }

            /** @var Collection<int, CustomerStockItem> $stockItems */
            $stockItems = CustomerStockItem::query()
                ->where('customer_stock_id', $customerStock->id)
                ->whereIn('id', $requestedQuantities->keys())
                ->with('orderItem.services')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($stockItems->count() !== $requestedQuantities->count()) {
                throw ValidationException::withMessages([
                    'quantities' => 'Một sản phẩm được chọn không thuộc lô tồn kho này.',
                ]);
            }

            $grossProductAmounts = [];
            $serviceSnapshotsByItem = [];

            foreach ($requestedQuantities as $itemId => $quantity) {
                $stockItem = $stockItems->get($itemId);

                if ($quantity > $stockItem->remainingQuantity()) {
                    throw ValidationException::withMessages([
                        "quantities.{$itemId}" => 'Số lượng xuất vượt quá số lượng còn trong kho.',
                    ]);
                }

                $grossProductAmounts[$itemId] = $quantity * (float) $stockItem->orderItem->unit_price;
                // Mỗi dịch vụ của Order Item được phân bổ theo đúng quantity sản phẩm xuất trong đợt này.
                $serviceSnapshotsByItem[$itemId] = $stockItem->orderItem->services
                    ->map(fn ($service): array => [
                        'order_item_service_id' => $service->id,
                        'service_name' => $service->service_name,
                        'quantity' => $quantity,
                        'unit_price' => (float) $service->unit_price,
                        'subtotal' => round($quantity * (float) $service->unit_price, 2),
                    ])
                    ->all();
            }

            $isFinalRelease = $this->isFinalRelease($customerStock->id, $requestedQuantities);
            $grossProductAmount = array_sum($grossProductAmounts);
            $grossServiceAmount = collect($serviceSnapshotsByItem)->flatten(1)->sum('subtotal');
            [$allocatedDiscount, $allocatedShippingFee, $reconciliationAdjustment, $releaseTotal] = $this->calculateReleaseAmounts(
                $order,
                $grossProductAmount + $grossServiceAmount,
                $customerStock,
                $isFinalRelease,
            );
            $release = $customerStock->releases()->create([
                'uuid' => (string) Str::uuid(),
                'release_code' => $this->nextReleaseCode($order, $customerStock),
                'released_at' => now(),
                'gross_product_amount' => $grossProductAmount,
                'gross_service_amount' => $grossServiceAmount,
                'allocated_discount' => $allocatedDiscount,
                'reconciliation_adjustment' => $reconciliationAdjustment,
                'total_amount' => $releaseTotal,
                'allocated_shipping_fee' => $allocatedShippingFee,
                'note' => filled($note) ? $note : null,
                'created_by' => $actorId,
            ]);

            foreach ($requestedQuantities as $itemId => $quantity) {
                $stockItem = $stockItems->get($itemId);
                // amount của dòng chỉ giữ gross sản phẩm; dịch vụ, discount và shipping có snapshot riêng.
                $releaseItem = $release->items()->create([
                    'customer_stock_item_id' => $stockItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $stockItem->orderItem->unit_price,
                    'amount' => $grossProductAmounts[$itemId],
                ]);

                foreach ($serviceSnapshotsByItem[$itemId] as $serviceSnapshot) {
                    $releaseItem->services()->create($serviceSnapshot);
                }

                $stockItem->increment('released_quantity', $quantity);
            }

            $order->forceFill([
                'fulfillment_status' => $isFinalRelease
                    ? FulfillmentStatus::FullyReleased
                    : FulfillmentStatus::PartiallyReleased,
                'is_delivered' => $isFinalRelease,
            ])->saveQuietly();

            $this->activityLogger->log($order, 'customer_stock.released', 'Đã xuất hàng từ kho khách hàng', [
                'customer_stock_id' => $customerStock->id,
                'stock_release_id' => $release->id,
                'release_code' => $release->release_code,
                'total_quantity' => $requestedQuantities->sum(),
                'total_amount' => $release->total_amount,
                'gross_service_amount' => $release->gross_service_amount,
                'allocated_discount' => $release->allocated_discount,
                'allocated_shipping_fee' => $release->allocated_shipping_fee,
            ]);

            // Ghi nhận xuất kho trước; sau đó mới xác nhận Shipping để timeline đúng nghiệp vụ thực tế.
            $order->shipping()->create([
                'stock_release_id' => $release->id,
                'status' => StatusApp::value('shipping.status', 'delivered'),
                'shipped_at' => now(),
                'delivered_at' => now(),
                'confirmed_by' => $actorId,
            ]);

            return $release->load(['items.customerStockItem.orderItem.productSku.product', 'items.services', 'shipping']);
        });
    }

    private function ensureOrderCanRelease(Order $order): void
    {
        if ($order->status !== StatusApp::value('order.status', 'completed') || $order->fulfillment_mode !== FulfillmentMode::CustomerStock) {
            throw ValidationException::withMessages([
                'customerStock' => 'Chỉ được xuất kho cho đơn lưu kho đã hoàn thành sản xuất.',
            ]);
        }

        if ($order->fulfillment_status === FulfillmentStatus::FullyReleased) {
            throw ValidationException::withMessages([
                'customerStock' => 'Đơn hàng này đã được xuất hết.',
            ]);
        }
    }

    /** @param Collection<int, int> $requestedQuantities */
    private function isFinalRelease(int $customerStockId, Collection $requestedQuantities): bool
    {
        return CustomerStockItem::query()
            ->where('customer_stock_id', $customerStockId)
            ->get()
            ->every(function (CustomerStockItem $item) use ($requestedQuantities): bool {
                return $item->released_quantity + $requestedQuantities->get($item->id, 0) >= $item->received_quantity;
            });
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    private function calculateReleaseAmounts(
        Order $order,
        float $grossReleaseAmount,
        CustomerStock $customerStock,
        bool $isFinalRelease,
    ): array {
        $previousReleaseTotal = (float) $customerStock->releases()->reorder()->sum('total_amount');
        $previousDiscount = (float) $customerStock->releases()->reorder()->sum('allocated_discount');
        $previousShippingFee = (float) $customerStock->releases()->sum('allocated_shipping_fee');
        $remainingDiscount = max(0, (float) $order->discount - $previousDiscount);
        $remainingShippingFee = max(0, (float) $order->shipping_fee - $previousShippingFee);

        if ($isFinalRelease) {
            $allocatedDiscount = round($remainingDiscount, 2);
            $allocatedShippingFee = round($remainingShippingFee, 2);
            $remainingOrderAmount = round(max(0, (float) $order->total_amount - $previousReleaseTotal), 2);
            $calculatedAmount = round($grossReleaseAmount - $allocatedDiscount + $allocatedShippingFee, 2);

            // Phiếu cuối đối chiếu với tổng Order để hấp thụ sai số làm tròn hoặc dữ liệu legacy đã thu trước đó.
            return [
                $allocatedDiscount,
                $allocatedShippingFee,
                round($remainingOrderAmount - $calculatedAmount, 2),
                $remainingOrderAmount,
            ];
        }

        if ((float) $order->subtotal <= 0) {
            return [0.0, 0.0, 0.0, 0.0];
        }

        // Cả giảm giá và phí giao hàng được phân bổ theo gross sản phẩm cộng dịch vụ của đợt xuất.
        $ratio = $grossReleaseAmount / (float) $order->subtotal;
        $allocatedDiscount = round(min($remainingDiscount, (float) $order->discount * $ratio), 2);
        $allocatedShippingFee = round(min($remainingShippingFee, (float) $order->shipping_fee * $ratio), 2);
        $releaseTotal = round(max(0, $grossReleaseAmount - $allocatedDiscount + $allocatedShippingFee), 2);

        return [
            $allocatedDiscount,
            $allocatedShippingFee,
            0.0,
            $releaseTotal,
        ];
    }

    private function nextReleaseCode(Order $order, CustomerStock $customerStock): string
    {
        $sequence = $customerStock->releases()->count() + 1;

        return sprintf('XK-%s-%03d', $order->order_code, $sequence);
    }
}
