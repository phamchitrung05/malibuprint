<?php

namespace Tests\Feature;

use App\Enums\ShippingMethod;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Service;
use App\Models\User;
use App\Services\PrintDocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_print_order_with_real_relational_data(): void
    {
        $user = User::factory()->create(['name' => 'Nhân viên in đơn']);
        $order = $this->createPrintableOrder($user, 'PRINT-001');
        $document = app(PrintDocumentFactory::class)->forOrder($order);

        $this->assertSame(200000.0, $document['items'][0]['total']);
        $this->assertSame([
            'name' => 'Dịch vụ in ly',
            'quantity' => 2,
            'unit_price' => 25000.0,
            'total' => 50000.0,
        ], $document['items'][0]['services'][0]);

        $this->actingAs($user)
            ->get(route('orders.print', $order))
            ->assertOk()
            ->assertSee('ĐƠN ĐẶT HÀNG')
            ->assertSee('PRINT-001')
            ->assertSee('Khách hàng in thử')
            ->assertSee('PRINT-SKU-PRINT-001')
            ->assertSee('Dịch vụ in ly')
            ->assertSeeInOrder(['Sản phẩm PRINT-001', 'Dịch vụ: Dịch vụ in ly'])
            ->assertSeeHtml('<tr class="service-row">')
            ->assertSee('Chưa thanh toán')
            ->assertSee('25.000')
            ->assertSee('265.000 VNĐ')
            ->assertSee('In đơn hàng');
    }

    public function test_authenticated_user_can_print_selected_orders_in_requested_order(): void
    {
        $user = User::factory()->create();
        $firstOrder = $this->createPrintableOrder($user, 'PRINT-FIRST');
        $secondOrder = $this->createPrintableOrder($user, 'PRINT-SECOND');

        $component = Livewire::actingAs($user)
            ->test(ListOrders::class)
            ->assertTableBulkActionExists('printOrders');

        $printAction = $component->instance()->getTable()->getBulkAction('printOrders');
        $clickHandler = $printAction?->getExtraAttributes()['x-on:click.prevent'] ?? '';

        // URL được dựng từ selection Alpine lúc click, không dùng danh sách record đã render từ lần trước.
        $this->assertSame(route('orders.print.bulk'), $printAction?->getUrl());
        $this->assertStringContainsString('[...selectedRecords]', $clickHandler);
        $this->assertStringContainsString('deselectAllRecords()', $clickHandler);

        $this->actingAs($user)
            ->get(route('orders.print.bulk', [
                'ids' => [$secondOrder->id, $firstOrder->id],
            ]))
            ->assertOk()
            ->assertSee('In 2 đơn hàng')
            ->assertSeeInOrder(['PRINT-SECOND', 'PRINT-FIRST']);
    }

    public function test_printed_order_shows_tracking_code_only_for_best_express(): void
    {
        $user = User::factory()->create();
        $bestExpressOrder = $this->createPrintableOrder($user, 'PRINT-BEST');
        $bestExpressOrder->forceFill([
            'shipping_method' => ShippingMethod::BestExpress,
            'shipping_tracking_code' => 'BEST-PRINT-001',
        ])->save();
        $standardOrder = $this->createPrintableOrder($user, 'PRINT-STANDARD');

        $this->actingAs($user)
            ->get(route('orders.print', $bestExpressOrder))
            ->assertOk()
            ->assertSeeInOrder([
                'Ngày giao hàng dự kiến',
                'BEST',
                'EXPRESS',
                'BEST-PRINT-001',
            ])
            ->assertSeeHtml('data-best-express-tracking');

        $this->actingAs($user)
            ->get(route('orders.print', $standardOrder))
            ->assertOk()
            ->assertDontSeeHtml('data-best-express-tracking');
    }

    public function test_print_routes_require_authentication(): void
    {
        $user = User::factory()->create();
        $order = $this->createPrintableOrder($user, 'PRINT-PRIVATE');

        $loginUrl = route('filament.admin.auth.login');

        $this->get(route('orders.print', $order))->assertRedirect($loginUrl);
        $this->get(route('orders.print.bulk', ['ids' => [$order->id]]))->assertRedirect($loginUrl);
    }

    private function createPrintableOrder(User $user, string $code): Order
    {
        $customer = Customer::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Khách hàng in thử',
            'phone' => '0900000011',
            'address' => '123 Đường In Ấn',
        ]);
        $product = Product::query()->create([
            'name' => "Sản phẩm {$code}",
            'product_type' => 'in_ly',
            'unit' => 'cái',
            'note' => 'Mô tả sản phẩm thực tế',
        ]);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => "PRINT-SKU-{$code}",
            'price' => 100000,
            'stock' => 100,
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'order_code' => $code,
            'customer_id' => $customer->id,
            'order_date' => now(),
            'delivery_date' => today()->addDays(3),
            'status' => 'pending',
            'discount' => 10000,
            'shipping_fee' => 25000,
            'note' => 'Ghi chú in từ Order',
            'created_by' => $user->id,
        ]);
        $item = $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 2,
            'unit_price' => 100000,
            'subtotal' => 200000,
        ]);
        $service = Service::query()->where('code', Service::CUP_PRINTING_CODE)->firstOrFail();
        $item->services()->create([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);
        $order->recalculateTotals();

        return $order->refresh();
    }
}
