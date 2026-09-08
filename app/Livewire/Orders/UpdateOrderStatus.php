<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Services\CustomerStockManager;
use App\Services\OrderActivityLogger;
use App\Services\OrderInventoryManager;
use App\Services\PaymentManager;
use App\Services\ShippingManager;
use App\Support\StatusApp;
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
        $this->transitionProduction(
            StatusApp::value('order.status', 'pending'),
            StatusApp::value('order.status', 'processing'),
            'Đã bắt đầu xử lý đơn hàng',
        );
    }

    public function completeProduction(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            $processing = StatusApp::value('order.status', 'processing');
            $completed = StatusApp::value('order.status', 'completed');
            abort_unless($order->status === $processing, 422);

            // Tồn đã trừ lúc tạo Order; hoàn thành chỉ khóa allocation thành consumed.
            app(OrderInventoryManager::class)->consumeForCompletedOrder($order);
            $order->status = $completed;
            $order->saveQuietly();

            // Đơn lưu kho được nhập toàn bộ thành phẩm ngay trong transaction hoàn thành sản xuất.
            app(CustomerStockManager::class)->createForCompletedOrder($order->id, auth()->id());

            app(OrderActivityLogger::class)->log($order, 'order.status_changed', 'Đã hoàn thành sản xuất', [
                'old' => ['status' => $processing],
                'new' => ['status' => $completed],
            ]);
        });

        $this->updatedSuccessfully('Đã hoàn thành sản xuất');
    }

    public function cancelOrder(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            $cancellableStatuses = [
                StatusApp::value('order.status', 'pending'),
                StatusApp::value('order.status', 'processing'),
            ];
            abort_unless(in_array($order->status, $cancellableStatuses, true), 422);

            $oldStatus = $order->status;
            // Hoàn tồn trước khi đổi status; mọi thay đổi vẫn nằm trong cùng transaction với việc hủy Order.
            app(OrderInventoryManager::class)->releaseForCancelledOrder($order, auth()->id());
            $order->status = StatusApp::value('order.status', 'cancelled');
            $order->saveQuietly();

            app(OrderActivityLogger::class)->log($order, 'order.cancelled', 'Đã hủy đơn hàng', [
                'old' => ['status' => $oldStatus],
                'new' => ['status' => $order->status],
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
        abort_unless(auth()->check(), 403);

        app(ShippingManager::class)->confirmSingleOrder($this->orderId, auth()->id());

        $this->updatedSuccessfully('Đã xác nhận giao hàng');
    }

    public function render(): View
    {
        $order = $this->getOrder()->loadMissing(['customer', 'payments', 'shipping']);
        $paidAmount = (float) $order->payments
            ->where('status', StatusApp::value('payment.status', 'completed'))
            ->sum('amount');

        return view('livewire.orders.update-order-status', [
            'order' => $order,
            'paidAmount' => $paidAmount,
            'remainingAmount' => max(0, (float) $order->total_amount - $paidAmount),
        ]);
    }

    private function transitionProduction(string $from, string $to, string $description): void
    {
        DB::transaction(function () use ($from, $to, $description): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === $from && StatusApp::canTransition('order.status', $from, $to), 422);

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
