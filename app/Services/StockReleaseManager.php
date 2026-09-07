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
                ->with('orderItem')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($stockItems->count() !== $requestedQuantities->count()) {
                throw ValidationException::withMessages([
                    'quantities' => 'Một sản phẩm được chọn không thuộc lô tồn kho này.',
                ]);
            }

            $grossAmounts = [];

            foreach ($requestedQuantities as $itemId => $quantity) {
                $stockItem = $stockItems->get($itemId);

                if ($quantity > $stockItem->remainingQuantity()) {
                    throw ValidationException::withMessages([
                        "quantities.{$itemId}" => 'Số lượng xuất vượt quá số lượng còn trong kho.',
                    ]);
                }

                $grossAmounts[$itemId] = $quantity * (float) $stockItem->orderItem->unit_price;
            }

            $isFinalRelease = $this->isFinalRelease($customerStock->id, $requestedQuantities);
            [$productAmount, $allocatedShippingFee] = $this->calculateReleaseAmounts(
                $order,
                array_sum($grossAmounts),
                $customerStock,
                $isFinalRelease,
            );
            $releaseTotal = $productAmount + $allocatedShippingFee;
            $release = $customerStock->releases()->create([
                'uuid' => (string) Str::uuid(),
                'release_code' => $this->nextReleaseCode($order, $customerStock),
                'released_at' => now(),
                'total_amount' => $releaseTotal,
                'allocated_shipping_fee' => $allocatedShippingFee,
                'note' => filled($note) ? $note : null,
                'created_by' => $actorId,
            ]);

            // Dòng sản phẩm không chứa phí giao hàng; phí được giữ riêng trên chứng từ để dễ đối soát.
            $productAmountInCents = (int) round($productAmount * 100);
            $allocatedCents = 0;
            $lastItemId = $requestedQuantities->keys()->last();
            $grossReleaseTotal = array_sum($grossAmounts);

            foreach ($requestedQuantities as $itemId => $quantity) {
                $stockItem = $stockItems->get($itemId);
                // Tính bằng đơn vị nhỏ nhất và chặn theo số dư để không sinh dòng âm do làm tròn nhiều sản phẩm.
                $remainingCents = max(0, $productAmountInCents - $allocatedCents);
                $lineCents = $itemId === $lastItemId
                    ? $remainingCents
                    : min(
                        $remainingCents,
                        (int) round($productAmountInCents * ($grossAmounts[$itemId] / max($grossReleaseTotal, 1))),
                    );
                $lineAmount = $lineCents / 100;

                $release->items()->create([
                    'customer_stock_item_id' => $stockItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $stockItem->orderItem->unit_price,
                    'amount' => $lineAmount,
                ]);

                $stockItem->increment('released_quantity', $quantity);
                $allocatedCents += $lineCents;
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

            return $release->load(['items.customerStockItem.orderItem.productSku.product', 'shipping']);
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

    /** @return array{0: float, 1: float} */
    private function calculateReleaseAmounts(
        Order $order,
        float $grossReleaseAmount,
        CustomerStock $customerStock,
        bool $isFinalRelease,
    ): array {
        $orderProductAmount = max(0, (float) $order->total_amount - (float) $order->shipping_fee);
        $previousProductAmount = (float) $customerStock->releases()
            // Quan hệ mặc định sắp xếp mới nhất; aggregate MySQL phải bỏ ORDER BY không cần thiết.
            ->reorder()
            ->selectRaw('COALESCE(SUM(total_amount - allocated_shipping_fee), 0) as total')
            ->value('total');
        $previousShippingFee = (float) $customerStock->releases()->sum('allocated_shipping_fee');
        $remainingProductAmount = max(0, $orderProductAmount - $previousProductAmount);
        $remainingShippingFee = max(0, (float) $order->shipping_fee - $previousShippingFee);

        if ($isFinalRelease) {
            return [round($remainingProductAmount, 2), round($remainingShippingFee, 2)];
        }

        if ((float) $order->subtotal <= 0) {
            return [0.0, 0.0];
        }

        // Cả giảm giá và phí giao hàng được phân bổ theo tỷ lệ giá trị gốc của đợt xuất.
        $ratio = $grossReleaseAmount / (float) $order->subtotal;

        return [
            round(min($remainingProductAmount, $orderProductAmount * $ratio), 2),
            round(min($remainingShippingFee, (float) $order->shipping_fee * $ratio), 2),
        ];
    }

    private function nextReleaseCode(Order $order, CustomerStock $customerStock): string
    {
        $sequence = $customerStock->releases()->count() + 1;

        return sprintf('XK-%s-%03d', $order->order_code, $sequence);
    }
}
