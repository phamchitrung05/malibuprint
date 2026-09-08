<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderReplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_table_can_recreate_order_as_new_lifecycle(): void
    {
        $this->travelTo('2026-09-08 09:00:00');
        [$user, $sourceOrder, $sku] = $this->createSourceOrder(quantity: 2);

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertTableActionExists('printOrder')
            ->assertTableActionExists('recreateOrder')
            ->callTableAction('recreateOrder', $sourceOrder->getKey())
            ->assertNotified('Đã tạo lại đơn hàng');

        $newOrder = Order::query()->whereKeyNot($sourceOrder->id)->sole();
        $newItem = $newOrder->items()->with('services')->sole();
        $sourceItem = $sourceOrder->items()->with('services')->sole();

        $this->assertSame('ORD-080926-001', $newOrder->order_code);
        $this->assertSame($sourceOrder->customer_id, $newOrder->customer_id);
        $this->assertSame('2026-09-08', $newOrder->order_date->toDateString());
        $this->assertSame('2026-09-13', $newOrder->delivery_date->toDateString());
        $this->assertSame('pending', $newOrder->status);
        $this->assertSame(FulfillmentMode::CustomerStock, $newOrder->fulfillment_mode);
        $this->assertSame('pending', $newOrder->fulfillment_status->value);
        $this->assertFalse($newOrder->is_paid);
        $this->assertFalse($newOrder->is_delivered);
        $this->assertNull($newOrder->closed_at);
        $this->assertSame($sourceOrder->discount, $newOrder->discount);
        $this->assertSame($sourceOrder->shipping_fee, $newOrder->shipping_fee);
        $this->assertSame($sourceOrder->total_amount, $newOrder->total_amount);
        $this->assertSame($sourceItem->product_sku_id, $newItem->product_sku_id);
        $this->assertSame($sourceItem->quantity, $newItem->quantity);
        $this->assertSame($sourceItem->unit_price, $newItem->unit_price);
        $this->assertSame($sourceItem->services->sole()->service_name, $newItem->services->sole()->service_name);
        $this->assertSame($sourceItem->services->sole()->unit_price, $newItem->services->sole()->unit_price);
        $this->assertSame(2, $newOrder->inventoryAllocations()->sole()->quantity);
        $this->assertSame(98, $sku->refresh()->stock);
        $this->assertDatabaseCount('payment', 0);
        $this->assertDatabaseCount('shipping', 0);
        $this->assertDatabaseCount('customer_stock', 0);
    }

    public function test_recreating_order_rolls_back_when_stock_is_insufficient(): void
    {
        [$user, $sourceOrder, $sku] = $this->createSourceOrder(quantity: 101);

        Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->callTableAction('recreateOrder', $sourceOrder->getKey())
            ->assertNotified('Không thể tạo lại đơn hàng');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_inventory_allocations', 0);
        $this->assertSame(100, $sku->refresh()->stock);
    }

    /** @return array{User, Order, ProductSku} */
    private function createSourceOrder(int $quantity): array
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách tạo lại Order',
            'phone' => '0900000077',
        ]);
        $product = Product::query()->create([
            'name' => 'Sản phẩm tạo lại',
            'product_type' => 'in_ly',
            'unit' => 'cái',
        ]);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'RECREATE-'.uniqid(),
            'price' => 100000,
            'stock' => 100,
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'order_code' => 'SOURCE-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => '2026-09-01 09:00:00',
            'delivery_date' => '2026-09-06',
            'status' => 'completed',
            'fulfillment_mode' => FulfillmentMode::CustomerStock,
            'fulfillment_status' => 'fully_released',
            'closed_at' => '2026-09-06 17:00:00',
            'discount' => 10000,
            'shipping_fee' => 25000,
            'note' => 'Giữ nguyên nội dung đơn nguồn',
            'created_by' => $user->id,
            'is_paid' => true,
            'is_delivered' => true,
        ]);
        $item = $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => $quantity,
            'unit_price' => 100000,
            'subtotal' => $quantity * 100000,
        ]);
        $service = Service::query()->where('code', Service::CUP_PRINTING_CODE)->firstOrFail();
        $item->services()->create([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => 20000,
            'subtotal' => $quantity * 20000,
        ]);
        $order->recalculateTotals();

        return [$user, $order->refresh(), $sku];
    }
}
