<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Livewire\Orders\UpdateOrderStatus;
use App\Livewire\Orders\UpdateShippingMethod;
use App\Models\Customer;
use App\Models\Order;
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
            ->set('trackingCode', '  BEST-123456  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified('Đã cập nhật phương thức vận chuyển');

        $order->refresh();

        $this->assertSame(ShippingMethod::BestExpress, $order->shipping_method);
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
        $this->assertStringContainsString('data-tracking-code-suffix', $shippingTab);
    }

    public function test_switching_back_to_standard_clears_tracking_code(): void
    {
        [$user, $order] = $this->createOrder([
            'shipping_method' => ShippingMethod::BestExpress,
            'shipping_tracking_code' => 'BEST-654321',
        ]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('trackingCode', '')
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(ShippingMethod::Standard, $order->shipping_method);
        $this->assertNull($order->shipping_tracking_code);
    }

    public function test_customer_stock_order_keeps_standard_shipping_and_cannot_use_best_express(): void
    {
        [$user, $order] = $this->createOrder([
            'fulfillment_mode' => FulfillmentMode::CustomerStock,
        ]);

        $this->assertSame(ShippingMethod::Standard, $order->shipping_method);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('trackingCode', 'BEST-CUSTOMER-STOCK')
            ->call('save')
            ->assertHasErrors('shippingMethod');

        $order->refresh();

        $this->assertSame(ShippingMethod::Standard, $order->shipping_method);
        $this->assertNull($order->shipping_tracking_code);

        $shippingTab = $this->renderShippingTab($order);

        $this->assertStringNotContainsString('data-shipping-method-section', $shippingTab);
    }

    public function test_delivered_order_cannot_change_shipping_method(): void
    {
        [$user, $order] = $this->createOrder(['is_delivered' => true]);

        Livewire::actingAs($user)
            ->test(UpdateShippingMethod::class, ['orderId' => $order->id])
            ->set('trackingCode', 'BEST-DELIVERED')
            ->call('save')
            ->assertHasErrors('shippingMethod');

        $this->assertSame(ShippingMethod::Standard, $order->refresh()->shipping_method);
        $this->assertStringNotContainsString('data-shipping-method-section', $this->renderShippingTab($order));
    }

    public function test_delivered_best_express_order_keeps_read_only_tracking_section(): void
    {
        [, $order] = $this->createOrder([
            'is_delivered' => true,
            'shipping_method' => ShippingMethod::BestExpress,
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
            'shipping_method' => ShippingMethod::BestExpress,
            'shipping_tracking_code' => null,
        ])->saveQuietly();

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasErrors('shipping');

        $this->assertFalse($order->refresh()->is_delivered);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_order_table_only_renders_best_express_badge_for_matching_orders(): void
    {
        [$user] = $this->createOrder();
        [, $bestExpressOrder] = $this->createOrder([
            'shipping_method' => ShippingMethod::BestExpress,
            'shipping_tracking_code' => 'BEST-TABLE',
        ]);

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertTableColumnExists('shipping_method')
            ->assertSee('BEST')
            ->assertSee('EXPRESS');

        $badge = view('filament.tables.columns.best-express-badge', [
            'getRecord' => fn (): Order => $bestExpressOrder,
        ])->render();

        $this->assertStringContainsString('Giao bằng Best Express', $badge);
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
