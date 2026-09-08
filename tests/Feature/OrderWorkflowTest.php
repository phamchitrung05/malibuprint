<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
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
        $order->forceFill([
            'shipping_fee' => 50000,
            'total_amount' => 1050000,
        ])->saveQuietly();

        $this->actingAs($user);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction')
            ->call('confirmPayment')
            ->call('confirmShipping')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertTrue($order->is_paid);
        $this->assertTrue($order->is_delivered);
        $this->assertCount(1, $order->payments);
        $this->assertSame('1050000.00', $order->payments->sole()->amount);
        $this->assertCount(1, $order->shipping);
        $this->assertDatabaseCount('customer_stock', 0);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'event' => 'shipping.created',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'event' => 'order.closed',
            'description' => 'Đã kết thúc đơn hàng',
        ]);
        $this->assertSame(1, $order->activities()->where('event', 'order.closed')->count());
        $this->assertEquals($order->order_date->timestamp, $order->customer->refresh()->last_order->timestamp);
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
            ->call('confirmPayment')
            ->assertHasErrors('payment');

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasErrors('shipping');

        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_legacy_pending_payment_and_shipping_are_confirmed_without_creating_duplicates(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $order->forceFill(['status' => 'completed'])->saveQuietly();
        $payment = $order->payments()->create([
            'payment_date' => now(),
            'amount' => 0,
            'status' => 'pending',
        ]);
        $shipping = $order->shipping()->create([
            'status' => 'pending',
        ]);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmPayment')
            ->call('confirmShipping')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('payment', 1);
        $this->assertDatabaseCount('shipping', 1);
        $this->assertSame('completed', $payment->refresh()->status);
        $this->assertSame('1000000.00', $payment->amount);
        $this->assertSame('delivered', $shipping->refresh()->status);
        $this->assertTrue($order->refresh()->is_paid);
        $this->assertTrue($order->is_delivered);
        $this->assertNotNull($order->closed_at);
        $this->assertSame(1, $order->activities()->where('event', 'order.closed')->count());
    }

    public function test_payment_closes_order_when_it_is_the_last_independent_step(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction')
            ->call('confirmShipping')
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertTrue($order->is_delivered);
        $this->assertTrue($order->is_paid);
        $this->assertNotNull($order->closed_at);
        $this->assertSame(1, $order->activities()->where('event', 'order.closed')->count());
    }

    public function test_order_totals_are_recalculated_from_items_discount_and_shipping_fee(): void
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
        $order->shipping_fee = 30000;
        $order->recalculateTotals();

        $order->refresh();

        $this->assertSame('300000.00', $order->subtotal);
        $this->assertSame('50000.00', $order->discount);
        $this->assertSame('30000.00', $order->shipping_fee);
        $this->assertSame('280000.00', $order->total_amount);
    }

    public function test_order_table_prioritizes_status_and_nearest_delivery_date(): void
    {
        $this->travelTo('2026-09-08 09:00:00');

        $user = User::factory()->create();
        $pendingFar = $this->createOrder($user);
        $pendingFar->forceFill(['delivery_date' => today()->addDays(5)])->saveQuietly();
        $pendingNear = $this->createOrder($user);
        $pendingNear->forceFill(['delivery_date' => today()->addDay()])->saveQuietly();
        $processingFar = $this->createOrder($user);
        $processingFar->forceFill([
            'status' => 'processing',
            'delivery_date' => today()->subDays(4),
        ])->saveQuietly();
        $processingNear = $this->createOrder($user);
        $processingNear->forceFill([
            'status' => 'processing',
            'delivery_date' => today()->addDays(2),
        ])->saveQuietly();
        $completed = $this->createOrder($user);
        $completed->forceFill([
            'status' => 'completed',
            'delivery_date' => today()->addDays(10),
        ])->saveQuietly();
        $cancelled = $this->createOrder($user);
        $cancelled->forceFill(['status' => 'cancelled'])->saveQuietly();

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertCanSeeTableRecords([
                $pendingNear,
                $processingNear,
                $processingFar,
                $pendingFar,
                $completed,
                $cancelled,
            ], inOrder: true)
            ->assertSee('Còn 1 ngày')
            ->assertSee('Trễ 4 ngày')
            ->assertDontSee('Còn 10 ngày');
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
