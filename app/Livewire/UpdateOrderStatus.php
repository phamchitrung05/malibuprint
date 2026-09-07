<?php

namespace App\Livewire;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Services\CustomerStockManager;
use App\Services\OrderActivityLogger;
use App\Services\PaymentManager;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateOrderStatus extends Component
{
    #[Locked]
    public int $orderId;

    public ?string $paymentNote = null;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function startProcessing(): void
    {
        $this->transitionProduction('pending', 'processing', 'Đã bắt đầu xử lý đơn hàng');
    }

    public function completeProduction(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === 'processing', 422);

            $order->status = 'completed';
            $order->saveQuietly();

            // Đơn lưu kho được nhập toàn bộ thành phẩm ngay trong transaction hoàn thành sản xuất.
            app(CustomerStockManager::class)->createForCompletedOrder($order->id, auth()->id());

            app(OrderActivityLogger::class)->log($order, 'order.status_changed', 'Đã hoàn thành sản xuất', [
                'old' => ['status' => 'processing'],
                'new' => ['status' => 'completed'],
            ]);
        });

        $this->updatedSuccessfully('Đã hoàn thành sản xuất');
    }

    public function cancelOrder(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            abort_unless(in_array($order->status, ['pending', 'processing'], true), 422);

            $oldStatus = $order->status;
            $order->status = 'cancelled';
            $order->saveQuietly();

            app(OrderActivityLogger::class)->log($order, 'order.cancelled', 'Đã hủy đơn hàng', [
                'old' => ['status' => $oldStatus],
                'new' => ['status' => 'cancelled'],
            ]);
        });

        $this->updatedSuccessfully('Đã hủy đơn hàng');
    }

    public function confirmPayment(): void
    {
        $this->validate([
            'paymentNote' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(auth()->check(), 403);

        app(PaymentManager::class)->confirmSingleOrder(
            $this->orderId,
            $this->paymentNote,
            auth()->id(),
        );

        $this->reset('paymentNote');
        $this->updatedSuccessfully('Đã ghi nhận thanh toán');
    }

    public function confirmShipping(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === 'completed', 422, 'Chỉ được giao hàng khi đơn hàng đã hoàn thành.');
            abort_unless($order->fulfillment_mode === FulfillmentMode::Single, 422, 'Đơn lưu kho phải được giao bằng phiếu xuất kho.');
            abort_if($order->is_delivered, 422);

            $shippingData = [
                'status' => 'delivered',
                'shipped_at' => now(),
                'delivered_at' => now(),
                'confirmed_by' => auth()->id(),
            ];
            $pendingShipping = $order->shipping()->where('status', 'pending')->lockForUpdate()->first();

            if ($pendingShipping) {
                // Dữ liệu legacy có thể đã có Shipping pending; cập nhật record cũ thay vì tạo lần giao thứ hai.
                $pendingShipping->forceFill($shippingData)->saveQuietly();

                app(OrderActivityLogger::class)->log($order, 'shipping.confirmed', 'Đã xác nhận giao hàng chờ', [
                    'shipping_id' => $pendingShipping->id,
                    'status' => 'delivered',
                ]);
            } else {
                $order->shipping()->create($shippingData);
            }

            $order->is_delivered = true;
            $order->fulfillment_status = FulfillmentStatus::FullyReleased;
            $order->closed_at = $order->is_paid ? now() : null;
            $order->saveQuietly();
        });

        $this->updatedSuccessfully('Đã xác nhận giao hàng');
    }

    public function render(): View
    {
        $order = $this->getOrder()->loadMissing(['customer', 'payments', 'shipping']);
        $paidAmount = (float) $order->payments->where('status', 'completed')->sum('amount');

        return view('livewire.update-order-status', [
            'order' => $order,
            'paidAmount' => $paidAmount,
            'remainingAmount' => max(0, (float) $order->total_amount - $paidAmount),
        ]);
    }

    private function transitionProduction(string $from, string $to, string $description): void
    {
        DB::transaction(function () use ($from, $to, $description): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === $from, 422);

            $order->status = $to;
            $order->saveQuietly();

            app(OrderActivityLogger::class)->log($order, 'order.status_changed', $description, [
                'old' => ['status' => $from],
                'new' => ['status' => $to],
            ]);
        });

        $this->updatedSuccessfully($description);
    }

    private function lockedOrder(): Order
    {
        abort_unless(auth()->check(), 403);

        return Order::query()->lockForUpdate()->findOrFail($this->orderId);
    }

    private function getOrder(): Order
    {
        return Order::query()->findOrFail($this->orderId);
    }

    private function updatedSuccessfully(string $message): void
    {
        $this->dispatch('order-status-updated');

        Notification::make()->title($message)->success()->send();
    }
}
