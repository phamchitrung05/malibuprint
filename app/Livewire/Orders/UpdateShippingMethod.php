<?php

namespace App\Livewire\Orders;

use App\Enums\FulfillmentMode;
use App\Enums\ShippingMethod;
use App\Models\Order;
use App\Services\OrderActivityLogger;
use App\Support\StatusApp;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateShippingMethod extends Component
{
    #[Locked]
    public int $orderId;

    public ?string $trackingCode = null;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;

        $order = $this->getOrder();
        $this->trackingCode = $order->shipping_tracking_code;
    }

    public function save(): void
    {
        $data = $this->validate([
            'trackingCode' => ['nullable', 'string', 'max:100'],
        ], [
            'trackingCode.max' => 'Mã vận đơn không được vượt quá 100 ký tự.',
        ]);

        abort_unless(auth()->check(), 403);

        DB::transaction(function () use ($data): void {
            $order = Order::query()->lockForUpdate()->findOrFail($this->orderId);

            if ($order->fulfillment_mode !== FulfillmentMode::Single
                || $order->status !== StatusApp::value('order.status', 'completed')) {
                throw ValidationException::withMessages([
                    'shippingMethod' => 'Best Express chỉ áp dụng cho Order giao một lần đã hoàn thành sản xuất.',
                ]);
            }

            if ($order->is_delivered) {
                throw ValidationException::withMessages([
                    'shippingMethod' => 'Order đã giao hàng nên không thể đổi phương thức vận chuyển.',
                ]);
            }

            $oldMethod = $order->shipping_method ?? ShippingMethod::Standard;
            $oldTrackingCode = $order->shipping_tracking_code;
            $trackingCode = trim((string) ($data['trackingCode'] ?? '')) ?: null;
            $method = $trackingCode === null ? ShippingMethod::Standard : ShippingMethod::BestExpress;

            $order->forceFill([
                'shipping_method' => $method,
                'shipping_tracking_code' => $trackingCode,
            ])->saveQuietly();

            app(OrderActivityLogger::class)->log(
                $order,
                'shipping.method_updated',
                'Đã cập nhật phương thức vận chuyển',
                [
                    'old' => [
                        'shipping_method' => $oldMethod->value,
                        'shipping_tracking_code' => $oldTrackingCode,
                    ],
                    'new' => [
                        'shipping_method' => $method->value,
                        'shipping_tracking_code' => $trackingCode,
                    ],
                ],
            );

            $this->trackingCode = $trackingCode;
        });

        $this->dispatch('order-status-updated');

        Notification::make()
            ->title('Đã cập nhật phương thức vận chuyển')
            ->success()
            ->send();
    }

    public function render(): View
    {
        $order = $this->getOrder();

        return view('livewire.orders.update-shipping-method', [
            'canUpdate' => $order->fulfillment_mode === FulfillmentMode::Single
                && $order->status === StatusApp::value('order.status', 'completed')
                && ! $order->is_delivered,
            'isBestExpress' => $order->shipping_method === ShippingMethod::BestExpress,
            'isDelivered' => $order->is_delivered,
        ]);
    }

    private function getOrder(): Order
    {
        return Order::query()->findOrFail($this->orderId);
    }
}
