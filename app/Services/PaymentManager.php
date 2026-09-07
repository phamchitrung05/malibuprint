<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockRelease;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentManager
{
    public function __construct(private readonly OrderActivityLogger $activityLogger) {}

    /**
     * Xác nhận thu toàn bộ tiền cho đơn giao một lần.
     *
     * Payment chỉ là chứng từ xác nhận của admin nên không tạo record pending trước thời điểm thu tiền.
     */
    public function confirmSingleOrder(int $orderId, ?string $note, int $actorId): Payment
    {
        return DB::transaction(function () use ($orderId, $note, $actorId): Payment {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'completed' || $order->fulfillment_mode !== FulfillmentMode::Single) {
                throw ValidationException::withMessages([
                    'payment' => 'Chỉ được xác nhận tiền cho đơn giao một lần đã hoàn thành sản xuất.',
                ]);
            }

            if ($order->payments()->where('status', 'completed')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Đơn hàng này đã được xác nhận thanh toán.',
                ]);
            }

            $pendingPayment = $order->payments()->where('status', 'pending')->lockForUpdate()->first();
            $paymentData = [
                'payment_date' => now(),
                'amount' => $order->total_amount,
                'status' => 'completed',
                'note' => filled($note) ? $note : null,
                'confirmed_by' => $actorId,
            ];

            if ($pendingPayment) {
                // Dữ liệu legacy từng tạo phiếu pending; tái sử dụng record này để không nhân đôi chứng từ.
                $pendingPayment->forceFill($paymentData)->saveQuietly();
                $payment = $pendingPayment;

                $this->activityLogger->log($order, 'payment.confirmed', 'Đã xác nhận phiếu thanh toán chờ', [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                ]);
            } else {
                $payment = $order->payments()->create($paymentData);
            }

            $order->forceFill([
                'is_paid' => true,
            ])->saveQuietly();

            app(OrderClosureManager::class)->closeIfReady($order->fresh());

            return $payment;
        });
    }

    /**
     * Tạo đúng một Payment cho một phiếu xuất sau khi admin xác nhận đã thu tiền.
     */
    public function confirmStockRelease(int $customerStockId, int $stockReleaseId, ?string $note, int $actorId): Payment
    {
        return DB::transaction(function () use ($customerStockId, $stockReleaseId, $note, $actorId): Payment {
            $customerStock = CustomerStock::query()->lockForUpdate()->findOrFail($customerStockId);
            $order = Order::query()->lockForUpdate()->findOrFail($customerStock->order_id);
            $stockRelease = StockRelease::query()
                ->where('customer_stock_id', $customerStock->id)
                ->lockForUpdate()
                ->findOrFail($stockReleaseId);

            if ($order->status !== 'completed' || $order->fulfillment_mode !== FulfillmentMode::CustomerStock) {
                throw ValidationException::withMessages([
                    'payment' => 'Phiếu xuất không thuộc đơn lưu kho đã hoàn thành sản xuất.',
                ]);
            }

            if (! $stockRelease->shipping()->where('status', 'delivered')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Phiếu xuất chưa có xác nhận giao hàng hợp lệ.',
                ]);
            }

            if ($stockRelease->payment()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Phiếu xuất này đã được xác nhận thanh toán.',
                ]);
            }

            $payment = $order->payments()->create([
                'stock_release_id' => $stockRelease->id,
                'payment_date' => now(),
                'amount' => $stockRelease->total_amount,
                'status' => 'completed',
                'note' => filled($note) ? $note : null,
                'confirmed_by' => $actorId,
            ]);

            $hasRemainingStock = $customerStock->items()
                ->whereColumn('released_quantity', '<', 'received_quantity')
                ->exists();
            $hasUnpaidRelease = $customerStock->releases()
                ->whereDoesntHave('payment')
                ->exists();
            $confirmedAmount = (float) $order->payments()->where('status', 'completed')->sum('amount');
            $isClosed = ! $hasRemainingStock
                && ! $hasUnpaidRelease
                && $confirmedAmount >= (float) $order->total_amount;

            $order->forceFill([
                'is_paid' => $isClosed,
            ])->saveQuietly();

            app(OrderClosureManager::class)->closeIfReady($order->fresh());

            if ($isClosed) {
                $customerStock->forceFill(['closed_at' => now()])->save();
            }

            return $payment;
        });
    }
}
