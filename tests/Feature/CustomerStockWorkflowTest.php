<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Livewire\CustomerStockReleaseHistory;
use App\Livewire\ReleaseCustomerStock;
use App\Livewire\UpdateOrderStatus;
use App\Models\Customer;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\CustomerStockManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerStockWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_customer_stock_order_creates_one_stock_lot_with_order_items(): void
    {
        [$user, $order] = $this->createCustomerStockOrder(100);

        $this->actingAs($user);

        Livewire::test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction')
            ->assertHasNoErrors();

        $stock = CustomerStock::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame($order->customer_id, $stock->customer_id);
        $this->assertSame(100, $stock->items()->sole()->received_quantity);
        $this->assertSame(0, $stock->items()->sole()->released_quantity);
        $this->assertSame(FulfillmentStatus::Ready, $order->refresh()->fulfillment_status);

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->assertViewHas('totalRemainingQuantity', 100)
            ->assertViewHas('remainingStockValue', 1000000.0);

        // Gọi lại manager mô phỏng retry của request; unique order_id phải giữ đúng một lô và một dòng tồn.
        app(CustomerStockManager::class)->createForCompletedOrder($order->id, $user->id);
        $this->assertDatabaseCount('customer_stock', 1);
        $this->assertDatabaseCount('customer_stock_items', 1);
    }

    public function test_partial_release_creates_shipping_and_keeps_payment_unconfirmed(): void
    {
        [$user, $order, $stock] = $this->completedCustomerStockOrder(100, 50000);
        $stockItem = $stock->items()->sole();

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 10)
            ->call('release')
            ->assertHasNoErrors();

        $release = $stock->releases()->firstOrFail();

        $this->assertSame(10, $stockItem->refresh()->released_quantity);
        $this->assertSame('5000.00', $release->allocated_shipping_fee);
        $this->assertSame('105000.00', $release->total_amount);
        $this->assertSame('100000.00', $release->items()->sole()->amount);
        $this->assertNull($release->payment);
        $this->assertSame('delivered', $release->shipping->status);
        $this->assertFalse($order->refresh()->is_delivered);
        $this->assertSame(FulfillmentStatus::PartiallyReleased, $order->fulfillment_status);

        $activityEvents = $order->activities()->oldest('id')->pluck('event')->all();
        $this->assertLessThan(
            array_search('shipping.created', $activityEvents, true),
            array_search('customer_stock.released', $activityEvents, true),
        );
    }

    public function test_customer_stock_order_cannot_use_single_delivery_payment_actions(): void
    {
        [$user, $order] = $this->completedCustomerStockOrder(100);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmPayment')
            ->assertHasErrors('payment');

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('confirmShipping')
            ->assertHasErrors('shipping');

        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_release_cannot_exceed_remaining_stock(): void
    {
        [$user, , $stock] = $this->completedCustomerStockOrder(100);
        $stockItem = $stock->items()->sole();

        $this->actingAs($user);

        Livewire::test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 101)
            ->call('release')
            ->assertHasErrors("quantities.{$stockItem->id}");

        $this->assertSame(0, $stockItem->refresh()->released_quantity);
        $this->assertDatabaseCount('stock_releases', 0);
        $this->assertDatabaseCount('shipping', 0);
    }

    public function test_admin_confirmation_creates_exactly_one_payment_for_release(): void
    {
        [$user, $order, $stock] = $this->completedCustomerStockOrder(100);
        $stockItem = $stock->items()->sole();

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 10)
            ->call('release');

        $release = $stock->releases()->firstOrFail();

        Livewire::actingAs($user)
            ->test(CustomerStockReleaseHistory::class, ['customerStockId' => $stock->id])
            ->call('confirmPayment', $release->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payment', [
            'order_id' => $order->id,
            'stock_release_id' => $release->id,
            'amount' => 100000,
            'status' => 'completed',
            'confirmed_by' => $user->id,
        ]);
        $this->assertFalse($order->refresh()->is_paid);

        Livewire::actingAs($user)
            ->test(CustomerStockReleaseHistory::class, ['customerStockId' => $stock->id])
            ->call('confirmPayment', $release->id)
            ->assertHasErrors('payment');

        $this->assertDatabaseCount('payment', 1);
    }

    public function test_order_closes_only_after_all_stock_is_released_and_every_release_is_paid(): void
    {
        [$user, $order, $stock] = $this->completedCustomerStockOrder(25, 10001);
        $stockItem = $stock->items()->sole();

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 10)
            ->call('release');
        $firstRelease = $stock->releases()->oldest('id')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CustomerStockReleaseHistory::class, ['customerStockId' => $stock->id])
            ->call('confirmPayment', $firstRelease->id);

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 15)
            ->call('release');
        $secondRelease = $stock->releases()->latest('id')->firstOrFail();

        $this->assertSame('4000.40', $firstRelease->allocated_shipping_fee);
        $this->assertSame('6000.60', $secondRelease->allocated_shipping_fee);
        $this->assertSame('104000.40', $firstRelease->total_amount);
        $this->assertSame('156000.60', $secondRelease->total_amount);

        $this->assertTrue($order->refresh()->is_delivered);
        $this->assertFalse($order->is_paid);
        $this->assertNull($order->closed_at);

        Livewire::actingAs($user)
            ->test(CustomerStockReleaseHistory::class, ['customerStockId' => $stock->id])
            ->call('confirmPayment', $secondRelease->id)
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertTrue($order->is_delivered);
        $this->assertTrue($order->is_paid);
        $this->assertNotNull($order->closed_at);
        $this->assertNotNull($stock->refresh()->closed_at);
        $this->assertSame(FulfillmentStatus::FullyReleased, $order->fulfillment_status);
        $this->assertSame(1, $order->activities()->where('event', 'order.closed')->count());
        $this->assertDatabaseCount('payment', 2);
        $this->assertDatabaseCount('shipping', 2);
        $this->assertSame(260001.0, (float) $order->payments()->sum('amount'));
    }

    /** @return array{User, Order} */
    private function createCustomerStockOrder(int $quantity, float $shippingFee = 0): array
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách lưu kho',
            'phone' => '0900000001',
        ]);
        $product = Product::query()->create([
            'name' => 'Áo thành phẩm',
            'unit' => 'cái',
        ]);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-STOCK-'.uniqid(),
            'price' => 10000,
            'stock' => 0,
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'order_code' => 'STOCK-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'fulfillment_mode' => FulfillmentMode::CustomerStock,
            'subtotal' => $quantity * 10000,
            'discount' => 0,
            'shipping_fee' => $shippingFee,
            'total_amount' => ($quantity * 10000) + $shippingFee,
            'created_by' => $user->id,
        ]);

        $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => $quantity,
            'unit_price' => 10000,
            'subtotal' => $quantity * 10000,
        ]);

        return [$user, $order];
    }

    /** @return array{User, Order, CustomerStock} */
    private function completedCustomerStockOrder(int $quantity, float $shippingFee = 0): array
    {
        [$user, $order] = $this->createCustomerStockOrder($quantity, $shippingFee);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction');

        return [$user, $order->refresh(), CustomerStock::query()->where('order_id', $order->id)->firstOrFail()];
    }
}
