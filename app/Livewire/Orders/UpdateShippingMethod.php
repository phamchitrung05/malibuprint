<?php

namespace App\Livewire\Orders;

use App\Enums\ShippingMethod;
use App\Models\Driver;
use App\Models\Order;
use App\Models\ShippingProvider;
use App\Services\OrderActivityLogger;
use App\Support\StatusApp;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateShippingMethod extends Component
{
    #[Locked]
    public int $orderId;

    public ?string $trackingCode = null;

    public string $shippingMethod = ShippingMethod::Vehicle->value;

    public ?int $shippingProviderId = null;

    public ?int $driverId = null;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;

        $order = $this->getOrder();
        $this->trackingCode = $order->shipping_tracking_code;
        $this->shippingMethod = ($order->shipping_method ?? ShippingMethod::Vehicle)->value;
        $this->shippingProviderId = $order->shipping_provider_id;
        $this->driverId = $order->driver_id;
    }

    public function save(): void
    {
        $data = $this->validate([
            'shippingMethod' => ['required', Rule::enum(ShippingMethod::class)],
            'shippingProviderId' => [
                'required',
                'integer',
                Rule::exists('shipping_providers', 'id')->where('is_active', true),
            ],
            'trackingCode' => [
                Rule::requiredIf($this->shippingMethod === ShippingMethod::Express->value),
                'nullable',
                'string',
                'max:100',
            ],
            'driverId' => [
                Rule::requiredIf($this->shippingMethod === ShippingMethod::Vehicle->value),
                'nullable',
                'integer',
                Rule::exists('drivers', 'id')->where('is_active', true),
            ],
        ], [
            'shippingProviderId.required' => 'Vui lòng chọn đơn vị vận chuyển.',
            'shippingProviderId.exists' => 'Đơn vị vận chuyển không còn hoạt động.',
            'trackingCode.required' => 'Vui lòng nhập mã vận đơn.',
            'trackingCode.max' => 'Mã vận đơn không được vượt quá 100 ký tự.',
            'driverId.required' => 'Vui lòng chọn tài xế.',
            'driverId.exists' => 'Tài xế không còn hoạt động.',
        ]);

        abort_unless(auth()->check(), 403);

        DB::transaction(function () use ($data): void {
            $order = Order::query()->lockForUpdate()->findOrFail($this->orderId);

            if ($order->status !== StatusApp::value('order.status', 'completed')) {
                throw ValidationException::withMessages([
                    'shippingMethod' => 'Chỉ được cập nhật vận chuyển cho Order đã hoàn thành sản xuất.',
                ]);
            }

            if ($order->is_delivered) {
                throw ValidationException::withMessages([
                    'shippingMethod' => 'Order đã giao hàng nên không thể đổi phương thức vận chuyển.',
                ]);
            }

            $oldMethod = $order->shipping_method ?? ShippingMethod::Vehicle;
            $oldTrackingCode = $order->shipping_tracking_code;
            $oldProviderId = $order->shipping_provider_id;
            $oldDriverId = $order->driver_id;
            $method = ShippingMethod::from($data['shippingMethod']);
            $trackingCode = $method === ShippingMethod::Express
                ? trim((string) ($data['trackingCode'] ?? ''))
                : null;
            $driverId = $method === ShippingMethod::Vehicle ? (int) $data['driverId'] : null;

            $order->forceFill([
                'shipping_method' => $method,
                'shipping_tracking_code' => $trackingCode,
                'shipping_provider_id' => (int) $data['shippingProviderId'],
                'driver_id' => $driverId,
            ])->saveQuietly();

            app(OrderActivityLogger::class)->log(
                $order,
                'shipping.method_updated',
                'Đã cập nhật phương thức vận chuyển',
                [
                    'old' => [
                        'shipping_method' => $oldMethod->value,
                        'shipping_tracking_code' => $oldTrackingCode,
                        'shipping_provider_id' => $oldProviderId,
                        'driver_id' => $oldDriverId,
                    ],
                    'new' => [
                        'shipping_method' => $method->value,
                        'shipping_tracking_code' => $trackingCode,
                        'shipping_provider_id' => (int) $data['shippingProviderId'],
                        'driver_id' => $driverId,
                    ],
                ],
            );

            $this->trackingCode = $trackingCode;
            $this->shippingMethod = $method->value;
            $this->shippingProviderId = (int) $data['shippingProviderId'];
            $this->driverId = $driverId;
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
            'canUpdate' => $order->status === StatusApp::value('order.status', 'completed')
                && ! $order->is_delivered,
            'shippingMethod' => $order->shipping_method?->value ?? ShippingMethod::Vehicle->value,
            'isDelivered' => $order->is_delivered,
            'shippingProviders' => ShippingProvider::query()->where('is_active', true)->orderBy('name')->get(),
            'drivers' => Driver::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function getOrder(): Order
    {
        return Order::query()->findOrFail($this->orderId);
    }
}
