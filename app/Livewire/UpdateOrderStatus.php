<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\OrderActivityLogger;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateOrderStatus extends Component
{
    #[Locked]
    public int $orderId;

    public ?string $paymentAmount = null;

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
        $this->transitionProduction('processing', 'completed', 'Đã hoàn thành sản xuất');
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
            'paymentAmount' => ['required', 'numeric', 'gt:0'],
            'paymentNote' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === 'completed', 422, 'Chỉ được thu tiền khi đơn hàng đã hoàn thành.');

            $paidAmount = (float) $order->payments()->where('status', 'completed')->sum('amount');
            $remainingAmount = max(0, (float) $order->total_amount - $paidAmount);
            abort_if((float) $this->paymentAmount > $remainingAmount, 422, 'Số tiền thu vượt quá số tiền còn lại.');

            $order->payments()->create([
                'payment_date' => now(),
                'amount' => $this->paymentAmount,
                'status' => 'completed',
                'note' => $this->paymentNote,
                'confirmed_by' => auth()->id(),
            ]);

            $order->is_paid = $order->payments()->where('status', 'completed')->sum('amount') >= (float) $order->total_amount;
            $order->saveQuietly();
        });

        $this->reset('paymentAmount', 'paymentNote');
        $this->updatedSuccessfully('Đã ghi nhận thanh toán');
    }

    public function confirmShipping(): void
    {
        DB::transaction(function (): void {
            $order = $this->lockedOrder();
            abort_unless($order->status === 'completed', 422, 'Chỉ được giao hàng khi đơn hàng đã hoàn thành.');
            abort_if($order->is_delivered, 422);

            $order->shipping()->create([
                'status' => 'delivered',
                'shipped_at' => now(),
                'delivered_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            $order->is_delivered = true;
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
