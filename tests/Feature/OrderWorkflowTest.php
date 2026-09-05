<?php

namespace Tests\Feature;

use App\Livewire\UpdateOrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_follow_production_payment_and_shipping_workflows(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $this->actingAs($user);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction')
            ->set('paymentAmount', '600000')
            ->call('confirmPayment')
            ->set('paymentAmount', '400000')
            ->call('confirmPayment')
            ->call('confirmShipping')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertTrue($order->is_paid);
        $this->assertTrue($order->is_delivered);
        $this->assertCount(2, $order->payments);
        $this->assertCount(1, $order->shipping);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'event' => 'shipping.created',
        ]);
    }

    public function test_pending_order_can_be_cancelled_without_a_reason(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $this->actingAs($user);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('cancelOrder')
            ->assertHasNoErrors();

        $activity = $order->activities()->where('event', 'order.cancelled')->firstOrFail();

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame($user->id, $activity->causer_id);
    }

    public function test_payment_and_shipping_are_blocked_until_production_is_completed(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $this->actingAs($user);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->set('paymentAmount', '1000000')
            ->call('confirmPayment')
            ->assertStatus(422);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertStatus(422);

        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_order_totals_are_recalculated_from_its_items_and_discount(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $product = Product::create([
            'name' => 'Sản phẩm thử nghiệm',
            'unit' => 'cái',
        ]);
        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-TEST',
            'price' => 150000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 2,
            'unit_price' => 150000,
            'subtotal' => 300000,
        ]);
        $order->discount = 50000;
        $order->recalculateTotals();

        $order->refresh();

        $this->assertSame('300000.00', $order->subtotal);
        $this->assertSame('50000.00', $order->discount);
        $this->assertSame('250000.00', $order->total_amount);
    }

    private function createOrder(User $user): Order
    {
        $customer = Customer::create([
            'name' => 'Khách thử nghiệm',
            'phone' => '0900000000',
        ]);

        return Order::create([
            'order_code' => 'TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_amount' => 1000000,
            'created_by' => $user->id,
        ]);
    }
}
