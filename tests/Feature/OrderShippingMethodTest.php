<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Livewire\Orders\UpdateOrderStatus;
use App\Livewire\Orders\UpdateShippingMethod;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderShippingMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_single_order_can_use_best_express_with_tracking_code(): void
    {
        [$user, $order] = $this->createOrder();

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::Express->value)
            ->set('trackingCode', '  BEST-123456  ')
            ->set('shippingProviderId', $order->shipping_provider_id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified('Đã cập nhật phương thức vận chuyển');

        $order->refresh();

        $this->assertSame(ShippingMethod::Express, $order->shipping_method);
        $this->assertSame('BEST-123456', $order->shipping_tracking_code);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'event' => 'shipping.method_updated',
        ]);

        $shippingTab = $this->renderShippingTab($order);
        $shippingMethodPosition = strpos($shippingTab, 'data-shipping-method-section');
        $shippingInformationPosition = strpos($shippingTab, 'Thông tin giao hàng');

        $this->assertNotFalse($shippingMethodPosition);
        $this->assertNotFalse($shippingInformationPosition);
        $this->assertLessThan($shippingInformationPosition, $shippingMethodPosition);
        $this->assertStringContainsString('shipping-tracking-code-'.$order->id, $shippingTab);
        $this->assertStringContainsString('Lưu thông tin vận chuyển', $shippingTab);
    }

    public function test_switching_back_to_standard_clears_tracking_code(): void
    {
        [$user, $order] = $this->createOrder([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-654321',
        ]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::Vehicle->value)
            ->set('driverId', Driver::query()->firstOrFail()->id)
            ->set('shippingProviderId', $order->shipping_provider_id)
            ->set('trackingCode', '')
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::Vehicle, $order->shipping_method);
        $this->assertNull($order->shipping_tracking_code);
    }

    public function test_customer_stock_order_can_update_shipping_method(): void
    {
        [$user, $order] = $this->createOrder([
            'fulfillment_mode' => FulfillmentMode::CustomerStock,
        ]);

        $this->assertSame(ShippingMethod::Vehicle, $order->shipping_method);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::Express->value)
            ->set('shippingProviderId', $order->shipping_provider_id)
            ->set('trackingCode', 'BEST-CUSTOMER-STOCK')
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::Express, $order->shipping_method);
        $this->assertSame('BEST-CUSTOMER-STOCK', $order->shipping_tracking_code);

        $shippingTab = $this->renderShippingTab($order);

        $this->assertStringContainsString('data-shipping-method-section', $shippingTab);
    }

    public function test_delivered_order_cannot_change_shipping_method(): void
    {
        [$user, $order] = $this->createOrder(['is_delivered' => true]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('trackingCode', 'BEST-DELIVERED')
            ->call('save')
            ->assertHasErrors('shippingMethod');

        $this->assertSame(ShippingMethod::Vehicle, $order->refresh()->shipping_method);
        $this->assertStringNotContainsString('data-shipping-method-section', $this->renderShippingTab($order));
    }

    public function test_delivered_best_express_order_keeps_read_only_tracking_section(): void
    {
        [, $order] = $this->createOrder([
            'is_delivered' => true,
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-DELIVERED',
        ]);

        $shippingTab = $this->renderShippingTab($order);

        $this->assertStringContainsString('data-shipping-method-section', $shippingTab);
        $this->assertStringContainsString('BEST-DELIVERED', $shippingTab);
        $this->assertStringContainsString('disabled', $shippingTab);
    }

    public function test_best_express_order_cannot_be_delivered_without_tracking_code(): void
    {
        [$user, $order] = $this->createOrder();
        $order->forceFill([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => null,
        ])->saveQuietly();

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasErrors('shipping');

        $this->assertFalse($order->refresh()->is_delivered);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_order_table_renders_best_express_badge_after_customer_name(): void
    {
        [$user] = $this->createOrder();
        [, $bestExpressOrder] = $this->createOrder([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-TABLE',
        ]);

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertTableColumnExists('customer.name')
            ->assertTableColumnDoesNotExist('shipping_method')
            ->assertSee('BEST')
            ->assertSee('EXPRESS');

        $customerName = view('filament.tables.columns.customer-name', [
            'name' => $bestExpressOrder->customer->name,
            'isBestExpress' => true,
        ])->render();

        $this->assertStringContainsString('Giao bằng Best Express', $customerName);
    }

    /** @return array{User, Order} */
    private function createOrder(array $attributes = []): array
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách vận chuyển',
            'phone' => '0900000099',
            'address' => '123 Nguyễn Huệ',
        ]);
        $vehicleProvider = ShippingProvider::query()->where('name', 'Giao hàng nội bộ')->firstOrFail();
        $bestExpressProvider = ShippingProvider::query()->where('name', 'Best Express')->firstOrFail();
        $driver = Driver::query()->create([
            'name' => 'Tài xế vận chuyển',
            'phone' => '0900000011',
            'is_active' => true,
        ]);
        $method = $attributes['shipping_method'] ?? ShippingMethod::Vehicle;
        $method = $method instanceof ShippingMethod ? $method : ShippingMethod::from($method);
        $order = Order::query()->create(array_merge([
            'order_code' => 'SHIP-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'delivery_date' => now()->addDay(),
            'status' => 'completed',
            'fulfillment_mode' => FulfillmentMode::Single,
            'fulfillment_status' => 'ready',
            'shipping_fee' => 30000,
            'created_by' => $user->id,
            'is_delivered' => false,
            'is_paid' => false,
            'shipping_method' => $method,
            'shipping_provider_id' => $method === ShippingMethod::Express ? $bestExpressProvider->id : $vehicleProvider->id,
            'driver_id' => $method === ShippingMethod::Vehicle ? $driver->id : null,
        ], $attributes));

        return [$user, $order->refresh()];
    }

    private function renderShippingTab(Order $order): string
    {
        return view('filament.resources.orders.actions.tabs.shipping', [
            'order' => $order->load(['customer', 'shipping']),
            'isMobile' => false,
        ])->render();
    }
}
