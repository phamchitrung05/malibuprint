<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Filament\Pages\ProductInventory;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Livewire\CustomerStocks\ReleaseCustomerStock;
use App\Livewire\Orders\UpdateOrderStatus;
use App\Models\Customer;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\InventoryManager;
use App\Services\OrderInventoryManager;
use App\Support\StatusApp;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_create_and_edit_order_persist_note_and_allocate_inventory(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách tạo Order',
            'phone' => '0900000099',
        ]);
        $product = Product::query()->create(['name' => 'Sản phẩm tạo Order', 'unit' => 'cái']);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'FILAMENT-INVENTORY',
            'price' => 10000,
            'stock' => 50,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);

        Livewire::actingAs($user)
            ->test(CreateOrder::class)
            ->fillForm([
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'fulfillment_mode' => FulfillmentMode::Single->value,
                'delivery_date' => now()->addDay()->toDateString(),
                'note' => 'Ghi chú khi tạo đơn',
                'items' => [[
                    'product_id' => $product->id,
                    'product_sku_id' => $sku->id,
                    'unit_price' => 10000,
                    'quantity' => 12,
                    'subtotal' => 120000,
                ]],
                'discount' => 0,
                'shipping_fee' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame(38, $sku->refresh()->stock);
        $this->assertSame(12, $order->inventoryAllocations()->sole()->quantity);
        $this->assertSame('Ghi chú khi tạo đơn', $order->note);

        Livewire::actingAs($user)
            ->test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['note' => 'Ghi chú sau khi cập nhật'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ghi chú sau khi cập nhật', $order->refresh()->note);
    }

    public function test_order_form_selects_first_available_sku_and_hides_products_without_remaining_skus(): void
    {
        $user = User::factory()->create();
        $product = Product::query()->create(['name' => 'Sản phẩm nhiều SKU', 'unit' => 'cái']);
        $unavailableProduct = Product::query()->create(['name' => 'Sản phẩm hết hàng', 'unit' => 'cái']);

        $outOfStockSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-01-HET-HANG',
            'price' => 5000,
            'stock' => 0,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        $firstAvailableSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-02-CON-HANG',
            'price' => 10000,
            'stock' => 5,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        $secondAvailableSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-03-CON-HANG',
            'price' => 20000,
            'stock' => 8,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        ProductSku::query()->create([
            'product_id' => $unavailableProduct->id,
            'sku_code' => 'SKU-SP-HET-HANG',
            'price' => 30000,
            'stock' => 0,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);

        $component = Livewire::actingAs($user)->test(CreateOrder::class);
        $firstItemKey = array_key_first($component->get('data.items'));

        $component
            ->set("data.items.{$firstItemKey}.product_id", $product->id)
            ->assertSet("data.items.{$firstItemKey}.product_sku_id", $firstAvailableSku->id)
            ->assertSet("data.items.{$firstItemKey}.unit_price", 10000.0)
            ->callFormComponentAction('items', 'add');

        $secondItemKey = array_key_last($component->get('data.items'));
        $component
            ->set("data.items.{$secondItemKey}.product_id", $product->id)
            ->assertSet("data.items.{$secondItemKey}.product_sku_id", $secondAvailableSku->id)
            ->assertSet("data.items.{$secondItemKey}.unit_price", 20000.0)
            ->callFormComponentAction('items', 'add');

        $thirdItemKey = array_key_last($component->get('data.items'));
        $component
            ->assertFormFieldExists(
                "items.{$firstItemKey}.product_sku_id",
                checkFieldUsing: function (Select $field) use ($outOfStockSku): bool {
                    $options = $field->getOptions();

                    return ($options[$outOfStockSku->id] ?? null) === 'SKU-01-HET-HANG (Hết hàng)'
                        && $field->isOptionDisabled($outOfStockSku->id, $options[$outOfStockSku->id]);
                },
            )
            ->assertFormFieldExists(
                "items.{$thirdItemKey}.product_id",
                checkFieldUsing: fn (Select $field): bool => ! array_key_exists($product->id, $field->getOptions())
                    && ! array_key_exists($unavailableProduct->id, $field->getOptions()),
            );
    }

    public function test_filament_does_not_persist_order_when_inventory_is_insufficient(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách thiếu tồn',
            'phone' => '0900000098',
        ]);
        $product = Product::query()->create(['name' => 'Sản phẩm thiếu tồn', 'unit' => 'cái']);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'FILAMENT-OUT-OF-STOCK',
            'price' => 10000,
            'stock' => 5,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);

        Livewire::actingAs($user)
            ->test(CreateOrder::class)
            ->fillForm([
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'fulfillment_mode' => FulfillmentMode::Single->value,
                'delivery_date' => now()->addDay()->toDateString(),
                'items' => [[
                    'product_id' => $product->id,
                    'product_sku_id' => $sku->id,
                    'unit_price' => 10000,
                    'quantity' => 12,
                    'subtotal' => 120000,
                ]],
                'discount' => 0,
                'shipping_fee' => 0,
            ])
            ->call('create')
            ->assertHasErrors(['inventory']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $sku->refresh()->stock);
        $this->assertDatabaseCount('order_inventory_allocations', 0);
    }

    public function test_order_allocates_every_sku_exactly_once(): void
    {
        [$user, $order, $firstSku, $secondSku] = $this->createOrderWithTwoSkus();

        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);
        // Retry cùng state không được trừ tồn hoặc tạo movement lần thứ hai.
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);

        $this->assertSame(70, $firstSku->refresh()->stock);
        $this->assertSame(40, $secondSku->refresh()->stock);
        $this->assertDatabaseHas('order_inventory_allocations', [
            'order_id' => $order->id,
            'product_sku_id' => $firstSku->id,
            'quantity' => 30,
            'status' => StatusApp::value('inventory_allocation.status', 'allocated'),
        ]);
        $this->assertSame(2, $order->inventoryMovements()->count());
    }

    public function test_insufficient_stock_rolls_back_every_inventory_change(): void
    {
        [$user, $order, $firstSku, $secondSku] = $this->createOrderWithTwoSkus();
        $secondSku->forceFill(['stock' => 5])->saveQuietly();

        try {
            app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);
            $this->fail('Đồng bộ phải thất bại khi một SKU không đủ tồn.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($secondSku->sku_code, $exception->getMessage());
        }

        $this->assertSame(100, $firstSku->refresh()->stock);
        $this->assertSame(5, $secondSku->refresh()->stock);
        $this->assertDatabaseCount('order_inventory_allocations', 0);
        $this->assertSame(0, $order->inventoryMovements()->count());
    }

    public function test_editing_order_only_applies_quantity_difference(): void
    {
        [$user, $order, $firstSku] = $this->createOrderWithTwoSkus();
        $order->items()->where('product_sku_id', '!=', $firstSku->id)->delete();
        $item = $order->items()->where('product_sku_id', $firstSku->id)->sole();

        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);
        $item->update(['quantity' => 40, 'subtotal' => 400000]);
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);
        $item->update(['quantity' => 25, 'subtotal' => 250000]);
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);

        $this->assertSame(75, $firstSku->refresh()->stock);
        $this->assertSame(25, $order->inventoryAllocations()->sole()->quantity);
        $this->assertSame(
            [-30, -10, 15],
            $order->inventoryMovements()->oldest('id')->pluck('quantity')->all(),
        );
    }

    public function test_cancelling_pending_order_restores_available_stock(): void
    {
        [$user, $order, $firstSku] = $this->createSingleSkuOrder();
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('cancelOrder')
            ->assertHasNoErrors();

        $this->assertSame(100, $firstSku->refresh()->stock);
        $this->assertSame(StatusApp::value('order.status', 'cancelled'), $order->refresh()->status);
        $this->assertSame(
            StatusApp::value('inventory_allocation.status', 'released'),
            $order->inventoryAllocations()->sole()->status,
        );
    }

    public function test_cancelling_processing_order_restores_available_stock(): void
    {
        [$user, $order, $firstSku] = $this->createSingleSkuOrder();
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('cancelOrder')
            ->assertHasNoErrors();

        $this->assertSame(100, $firstSku->refresh()->stock);
        $this->assertSame(
            StatusApp::value('inventory_allocation.status', 'released'),
            $order->inventoryAllocations()->sole()->status,
        );
    }

    public function test_completed_customer_stock_order_does_not_deduct_sku_twice(): void
    {
        [$user, $order, $sku] = $this->createSingleSkuOrder(FulfillmentMode::CustomerStock);
        app(OrderInventoryManager::class)->syncForOrder($order->id, $user->id);

        Livewire::actingAs($user)
            ->test(UpdateOrderStatus::class, ['orderId' => $order->id])
            ->call('startProcessing')
            ->call('completeProduction')
            ->assertHasNoErrors();

        $stock = CustomerStock::query()->where('order_id', $order->id)->firstOrFail();
        $stockItem = $stock->items()->sole();

        Livewire::actingAs($user)
            ->test(ReleaseCustomerStock::class, ['customerStockId' => $stock->id])
            ->set("quantities.{$stockItem->id}", 30)
            ->call('release')
            ->assertHasNoErrors();

        $this->assertSame(70, $sku->refresh()->stock);
        $this->assertSame(
            StatusApp::value('inventory_allocation.status', 'consumed'),
            $order->inventoryAllocations()->sole()->status,
        );
        $this->assertSame(2, $sku->inventoryMovements()->count()); // Số dư đầu kỳ và lần cấp cho Order.
    }

    public function test_manual_adjustment_updates_balance_and_records_reason(): void
    {
        [, , $sku] = $this->createSingleSkuOrder();
        $user = User::factory()->create();

        $movement = app(InventoryManager::class)->adjust($sku->id, -15, 'Kiểm kê thiếu thực tế', $user->id);

        $this->assertSame(85, $sku->refresh()->stock);
        $this->assertSame(-15, $movement->quantity);
        $this->assertSame(100, $movement->balance_before);
        $this->assertSame(85, $movement->balance_after);
        $this->assertSame('Kiểm kê thiếu thực tế', $movement->reason);
    }

    public function test_product_inventory_receive_action_increases_stock_and_records_movement(): void
    {
        [$user, , $sku] = $this->createSingleSkuOrder();

        Livewire::actingAs($user)
            ->test(ProductInventory::class)
            ->set('adjustmentQuantity', 5000)
            ->set('adjustmentReason', 'Nhập bổ sung từ nhà cung cấp')
            ->call('receiveInventory', $sku->id)
            ->assertHasNoErrors();

        $this->assertSame(5100, $sku->refresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $sku->id,
            'quantity' => 5000,
            'balance_before' => 100,
            'balance_after' => 5100,
            'reason' => 'Nhập bổ sung từ nhà cung cấp',
        ]);
    }

    public function test_product_inventory_decrease_action_reduces_stock_and_records_movement(): void
    {
        [$user, , $sku] = $this->createSingleSkuOrder();

        Livewire::actingAs($user)
            ->test(ProductInventory::class)
            ->set('decreaseQuantity', 25)
            ->set('decreaseReason', 'Xuất hủy hàng lỗi')
            ->call('decreaseInventory', $sku->id)
            ->assertHasNoErrors();

        $this->assertSame(75, $sku->refresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $sku->id,
            'quantity' => -25,
            'balance_before' => 100,
            'balance_after' => 75,
            'reason' => 'Xuất hủy hàng lỗi',
        ]);
    }

    public function test_inventory_page_displays_products_and_sku_inventory_modal(): void
    {
        [$user, , $firstSku, $secondSku] = $this->createOrderWithTwoSkus();
        $secondSku->forceFill(['stock' => 0])->saveQuietly();
        $product = $firstSku->product;

        Livewire::actingAs($user)
            ->test(ProductInventory::class)
            ->assertCanSeeTableRecords([$product])
            ->assertSee('Tổng tồn khả dụng')
            ->assertSee('Số SKU')
            ->mountTableAction('viewSkuInventory', $product->getKey())
            ->assertMountedActionModalSee([
                "Tồn kho SKU - {$product->name}",
                $firstSku->sku_code,
                $secondSku->sku_code,
                "Lịch sử xuất nhập: {$firstSku->sku_code}",
                "Order đang được cấp: {$firstSku->sku_code}",
            ]);
    }

    public function test_initialize_command_allocates_stock_for_existing_active_orders(): void
    {
        [, $order, $sku] = $this->createSingleSkuOrder();

        $this->artisan('inventory:initialize-active-orders', ['--apply' => true])
            ->assertSuccessful();

        $this->assertSame(70, $sku->refresh()->stock);
        $this->assertSame(30, $order->inventoryAllocations()->sole()->quantity);
    }

    /** @return array{User, Order, ProductSku, ProductSku} */
    private function createOrderWithTwoSkus(): array
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách tồn kho',
            'phone' => fake()->unique()->numerify('09########'),
        ]);
        $product = Product::query()->create(['name' => 'Sản phẩm tồn kho', 'unit' => 'cái']);
        $firstSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'INVENTORY-A-'.uniqid(),
            'price' => 10000,
            'stock' => 100,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        $secondSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'INVENTORY-B-'.uniqid(),
            'price' => 20000,
            'stock' => 50,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        $order = Order::query()->create([
            'order_code' => 'INVENTORY-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'created_by' => $user->id,
        ]);
        $order->items()->createMany([
            ['product_sku_id' => $firstSku->id, 'quantity' => 30, 'unit_price' => 10000, 'subtotal' => 300000],
            ['product_sku_id' => $secondSku->id, 'quantity' => 10, 'unit_price' => 20000, 'subtotal' => 200000],
        ]);

        return [$user, $order, $firstSku, $secondSku];
    }

    /** @return array{User, Order, ProductSku} */
    private function createSingleSkuOrder(FulfillmentMode $mode = FulfillmentMode::Single): array
    {
        [$user, $order, $sku] = $this->createOrderWithTwoSkus();
        $order->items()->where('product_sku_id', '!=', $sku->id)->delete();
        $order->forceFill(['fulfillment_mode' => $mode])->saveQuietly();

        return [$user, $order, $sku];
    }
}
