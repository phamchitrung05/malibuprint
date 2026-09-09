<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderItemServiceManager;
use App\Support\StatusApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderItemServiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_form_recalculates_service_amount_immediately_when_toggle_changes(): void
    {
        [$user, , $product, $sku] = $this->createOrderDependencies('in_ly');
        $component = Livewire::actingAs($user)->test(CreateOrder::class);
        $itemKey = array_key_first($component->get('data.items'));

        $component
            ->set("data.items.{$itemKey}.product_id", $product->id)
            ->assertSet("data.items.{$itemKey}.product_sku_id", $sku->id)
            ->set("data.items.{$itemKey}.quantity", 2)
            ->set("data.items.{$itemKey}.include_cup_printing_service", true)
            ->assertSet('data.subtotal', 360000.0)
            ->assertSet('data.total_amount', 360000.0)
            ->assertSee('Tiền dịch vụ (170.000đ × (2))');
    }

    public function test_order_form_sums_service_price_times_quantity_for_each_checked_item(): void
    {
        [$user, , $product, $firstSku] = $this->createOrderDependencies('in_ly');
        $secondSku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-SECOND-'.uniqid(),
            'price' => 10000,
            'stock' => 10,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);
        $component = Livewire::actingAs($user)->test(CreateOrder::class);
        $firstItemKey = array_key_first($component->get('data.items'));

        $component
            ->set("data.items.{$firstItemKey}.product_id", $product->id)
            ->assertSet("data.items.{$firstItemKey}.product_sku_id", $firstSku->id)
            ->set("data.items.{$firstItemKey}.quantity", 2)
            ->set("data.items.{$firstItemKey}.include_cup_printing_service", true)
            ->callFormComponentAction('items', 'add');

        $secondItemKey = array_key_last($component->get('data.items'));
        $component
            ->set("data.items.{$secondItemKey}.product_id", $product->id)
            ->assertSet("data.items.{$secondItemKey}.product_sku_id", $secondSku->id)
            ->set("data.items.{$secondItemKey}.include_cup_printing_service", true)
            ->assertSet('data.subtotal', 540000.0)
            ->assertSet('data.total_amount', 540000.0)
            ->assertSee('Tiền dịch vụ (170.000đ × (2 + 1))');
    }

    public function test_cup_printing_service_is_saved_per_order_item_and_included_in_order_total(): void
    {
        [$user, $customer, $product, $sku] = $this->createOrderDependencies('in_ly');

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
                    'quantity' => 2,
                    'subtotal' => 20000,
                    'include_cup_printing_service' => true,
                ]],
                'discount' => 0,
                'shipping_fee' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->latest('id')->firstOrFail();
        $serviceSnapshot = $order->itemServices()->sole();

        $this->assertSame('Dịch vụ in ly', $serviceSnapshot->service_name);
        $this->assertSame(2, $serviceSnapshot->quantity);
        $this->assertSame('170000.00', $serviceSnapshot->unit_price);
        $this->assertSame('340000.00', $serviceSnapshot->subtotal);
        $this->assertSame('360000.00', $order->subtotal);
        $this->assertSame('360000.00', $order->total_amount);
        $this->assertSame(8, $sku->refresh()->stock);

        $order->load(['items.productSku.product', 'items.services.service']);
        $this->view('filament.resources.orders.actions.tabs.products', ['order' => $order])
            ->assertSee('Thành tiền')
            ->assertSee('Dịch vụ in ly')
            ->assertSee('340.000đ')
            ->assertSee('360.000đ');
        $this->view('filament.resources.orders.actions.tabs.info', ['order' => $order])
            ->assertSee('Tiền dịch vụ')
            ->assertSee('340.000đ');
    }

    public function test_editing_item_quantity_keeps_service_price_snapshot_and_updates_service_subtotal(): void
    {
        [$user, $customer, $product, $sku] = $this->createOrderDependencies('in_ly');
        $order = $this->createOrderWithItem($user, $customer, $sku, 2);

        app(OrderItemServiceManager::class)->syncForOrder($order->id, [[
            'product_sku_id' => $sku->id,
            'include_cup_printing_service' => true,
        ]]);
        $order->recalculateTotals();

        Service::query()->where('code', Service::CUP_PRINTING_CODE)->update(['unit_price' => 200000]);

        $component = Livewire::actingAs($user)->test(EditOrder::class, ['record' => $order->getRouteKey()]);
        $itemKey = array_key_first($component->get('data.items'));

        $component
            ->assertSet("data.items.{$itemKey}.include_cup_printing_service", true)
            ->set("data.items.{$itemKey}.quantity", 3)
            ->call('save')
            ->assertHasNoFormErrors();

        $serviceSnapshot = $order->refresh()->itemServices()->sole();

        $this->assertSame(3, $serviceSnapshot->quantity);
        $this->assertSame('170000.00', $serviceSnapshot->unit_price);
        $this->assertSame('510000.00', $serviceSnapshot->subtotal);
        $this->assertSame('540000.00', $order->subtotal);

        $component = Livewire::actingAs($user)->test(EditOrder::class, ['record' => $order->getRouteKey()]);
        $itemKey = array_key_first($component->get('data.items'));
        $component
            ->set("data.items.{$itemKey}.include_cup_printing_service", false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('order_item_services', [
            'order_item_id' => $order->items()->sole()->id,
        ]);
        $this->assertSame('30000.00', $order->refresh()->subtotal);
    }

    public function test_service_is_removed_when_toggle_is_turned_off_and_rejected_for_paper_product(): void
    {
        [$user, $customer, , $cupSku] = $this->createOrderDependencies('in_ly');
        $cupOrder = $this->createOrderWithItem($user, $customer, $cupSku, 1);
        $manager = app(OrderItemServiceManager::class);
        $manager->syncForOrder($cupOrder->id, [[
            'product_sku_id' => $cupSku->id,
            'include_cup_printing_service' => true,
        ]]);

        $manager->syncForOrder($cupOrder->id, [[
            'product_sku_id' => $cupSku->id,
            'include_cup_printing_service' => false,
        ]]);

        $this->assertDatabaseMissing('order_item_services', [
            'order_item_id' => $cupOrder->items()->sole()->id,
        ]);

        [$paperUser, $paperCustomer, , $paperSku] = $this->createOrderDependencies('in_giay');
        $paperOrder = $this->createOrderWithItem($paperUser, $paperCustomer, $paperSku, 1);
        $manager->syncForOrder($paperOrder->id, [[
            'product_sku_id' => $paperSku->id,
            'include_cup_printing_service' => true,
        ]]);

        $this->assertDatabaseMissing('order_item_services', [
            'order_item_id' => $paperOrder->items()->sole()->id,
        ]);
    }

    public function test_service_resource_changes_catalog_price_without_changing_existing_snapshots(): void
    {
        [$user, $customer, , $oldSku] = $this->createOrderDependencies('in_ly');
        $oldOrder = $this->createOrderWithItem($user, $customer, $oldSku, 1);
        $manager = app(OrderItemServiceManager::class);
        $manager->syncForOrder($oldOrder->id, [[
            'product_sku_id' => $oldSku->id,
            'include_cup_printing_service' => true,
        ]]);
        $service = Service::query()->where('code', Service::CUP_PRINTING_CODE)->sole();

        Livewire::actingAs($user)
            ->test(ListServices::class)
            ->assertCanSeeTableRecords([$service]);

        Livewire::actingAs($user)
            ->test(EditService::class, ['record' => $service->getRouteKey()])
            ->fillForm([
                'name' => 'Dịch vụ in ly cao cấp',
                'unit_price' => 180000,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Dịch vụ in ly cao cấp', $service->refresh()->name);
        $this->assertSame('180000.00', $service->unit_price);
        $this->assertSame('170000.00', $oldOrder->itemServices()->sole()->unit_price);

        [$newUser, $newCustomer, , $newSku] = $this->createOrderDependencies('in_ly');
        $newOrder = $this->createOrderWithItem($newUser, $newCustomer, $newSku, 1);
        $manager->syncForOrder($newOrder->id, [[
            'product_sku_id' => $newSku->id,
            'include_cup_printing_service' => true,
        ]]);

        $this->assertSame('180000.00', $newOrder->itemServices()->sole()->unit_price);
    }

    /** @return array{User, Customer, Product, ProductSku} */
    private function createOrderDependencies(string $productType): array
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách dịch vụ',
            'phone' => fake()->unique()->numerify('09########'),
        ]);
        $product = Product::query()->create([
            'name' => 'Sản phẩm '.uniqid(),
            'product_type' => $productType,
            'unit' => 'cái',
        ]);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-'.uniqid(),
            'price' => 10000,
            'stock' => 10,
            'status' => StatusApp::value('product_sku.status', 'active'),
        ]);

        return [$user, $customer, $product, $sku];
    }

    private function createOrderWithItem(User $user, Customer $customer, ProductSku $sku, int $quantity): Order
    {
        $order = Order::query()->create([
            'order_code' => 'SERVICE-'.uniqid(),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'delivery_date' => now()->addDay(),
            'status' => StatusApp::default('order.status'),
            'subtotal' => $quantity * 10000,
            'discount' => 0,
            'shipping_fee' => 0,
            'total_amount' => $quantity * 10000,
            'created_by' => $user->id,
        ]);
        $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => $quantity,
            'unit_price' => 10000,
            'subtotal' => $quantity * 10000,
        ]);

        return $order;
    }
}
