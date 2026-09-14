<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Livewire\Orders\UpdateOrderStatus;
use App\Livewire\Orders\UpdateShippingMethod;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class OrderShippingMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_provider_schema_has_been_removed(): void
    {
        $this->assertFalse(Schema::hasTable('shipping_providers'));
        $this->assertFalse(Schema::hasColumn('orders', 'shipping_provider_id'));
    }

    public function test_order_form_separates_order_and_shipping_information_into_tabs(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateOrder::class)
            ->assertSee('Thông tin đơn hàng')
            ->assertSee('Thông tin giao hàng')
            ->assertSee('Giao hàng nội thành');
    }

    public function test_completed_single_order_can_use_express_delivery_with_tracking_code(): void
    {
        [$user, $order] = $this->createOrder();

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->assertSeeHtml('fi-select-input')
            ->assertDontSee('Đơn vị vận chuyển')
            ->set('shippingMethod', ShippingMethod::Express->value)
            ->set('trackingCode', '  BEST-123456  ')
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

    public function test_switching_to_vehicle_delivery_clears_tracking_code(): void
    {
        [$user, $order] = $this->createOrder([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-654321',
        ]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::Vehicle->value)
            ->set('driverId', Driver::query()->firstOrFail()->id)
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
            ->set('trackingCode', 'BEST-CUSTOMER-STOCK')
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::Express, $order->shipping_method);
        $this->assertSame('BEST-CUSTOMER-STOCK', $order->shipping_tracking_code);

        $shippingTab = $this->renderShippingTab($order);

        $this->assertStringContainsString('data-shipping-method-section', $shippingTab);
        $this->assertStringNotContainsString('Đơn vị vận chuyển', $shippingTab);
    }

    public function test_customer_pickup_needs_no_tracking_code_or_driver(): void
    {
        [$user, $order] = $this->createOrder([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'EXISTING-CODE',
        ]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::CustomerPickup->value)
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::CustomerPickup, $order->shipping_method);
        $this->assertNull($order->shipping_tracking_code);
        $this->assertNull($order->driver_id);
        $this->assertStringContainsString('Khách hàng tự tới lấy', $this->renderShippingTab($order));

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasNoErrors();

        $this->assertTrue($order->refresh()->is_delivered);
    }

    public function test_inner_city_delivery_needs_no_tracking_code_or_driver(): void
    {
        [$user, $order] = $this->createOrder([
            'shipping_method' => ShippingMethod::Vehicle,
        ]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('shippingMethod', ShippingMethod::InnerCity->value)
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::InnerCity, $order->shipping_method);
        $this->assertNull($order->shipping_tracking_code);
        $this->assertNull($order->driver_id);
        $this->assertStringContainsString('Giao hàng nội thành', $this->renderShippingTab($order));

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasNoErrors();

        $this->assertTrue($order->refresh()->is_delivered);
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

    public function test_delivered_express_order_keeps_tracking_information(): void
    {
        [, $order] = $this->createOrder([
            'is_delivered' => true,
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-DELIVERED',
        ]);

        $shippingTab = $this->renderShippingTab($order);

        $this->assertStringNotContainsString('data-shipping-method-section', $shippingTab);
        $this->assertStringContainsString('BEST-DELIVERED', $shippingTab);
        $this->assertStringContainsString('Mã giao hàng nhanh', $shippingTab);
    }

    public function test_express_order_cannot_be_delivered_without_tracking_code(): void
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

    public function test_order_table_renders_shipping_method_badge_after_customer_name(): void
    {
        [$user] = $this->createOrder();
        [, $expressOrder] = $this->createOrder([
            'shipping_method' => ShippingMethod::Express,
            'shipping_tracking_code' => 'BEST-TABLE',
        ]);

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertTableColumnExists('customer.name')
            ->assertTableColumnDoesNotExist('shipping_method')
            ->assertSee('BEST')
            ->assertSee('EXPRESS');

        $expressCustomerName = view('filament.tables.columns.customer-name', [
            'name' => $expressOrder->customer->name,
            'isExpress' => true,
        ])->render();
        $otherCustomerName = view('filament.tables.columns.customer-name', [
            'name' => 'Khách không chuyển phát nhanh',
            'isExpress' => false,
        ])->render();

        $this->assertStringContainsString('Giao bằng Best Express', $expressCustomerName);
        $this->assertStringContainsString('BEST', $expressCustomerName);
        $this->assertStringContainsString('EXPRESS', $expressCustomerName);
        $this->assertStringNotContainsString('BEST', $otherCustomerName);
        $this->assertStringNotContainsString('EXPRESS', $otherCustomerName);
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
