<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMode;
use App\Filament\Widgets\CupPrintingRevenueChart;
use App\Filament\Widgets\MonthlyRevenueStats;
use App\Filament\Widgets\TotalRevenueChart;
use App\Models\Customer;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Service;
use App\Models\StockRelease;
use App\Models\StockReleaseItem;
use App\Models\User;
use App\Services\MonthlyRevenueAnalytics;
use App\Support\StatusApp;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyRevenueAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_revenue_and_cup_printing_follow_completed_payment_month(): void
    {
        CarbonImmutable::setTestNow('2026-04-15 10:00:00');
        [$customer, $sku, $service] = $this->createCatalog();

        $marchOrder = $this->createSingleOrderWithService($customer, $sku, $service, 2, '2026-02-20');
        Payment::query()->create([
            'order_id' => $marchOrder->id,
            'payment_date' => '2026-03-10 09:00:00',
            'amount' => 250000,
            'status' => StatusApp::value('payment.status', 'completed'),
        ]);

        // Order tạo từ tháng trước chỉ được ghi nhận khi phiếu thu hoàn tất trong tháng 4.
        $aprilOrder = $this->createSingleOrderWithService($customer, $sku, $service, 3, '2026-03-05');
        Payment::query()->create([
            'order_id' => $aprilOrder->id,
            'payment_date' => '2026-04-02 09:00:00',
            'amount' => 375000,
            'status' => StatusApp::value('payment.status', 'completed'),
        ]);

        $stockOrder = $this->createCustomerStockOrderWithPaidRelease($customer, $sku, $service);
        Payment::query()->create([
            'order_id' => $stockOrder->id,
            'stock_release_id' => $stockOrder->customerStock->releases->first()->id,
            'payment_date' => '2026-04-08 09:00:00',
            'amount' => 125000,
            'status' => StatusApp::value('payment.status', 'completed'),
        ]);

        $pendingOrder = $this->createSingleOrderWithService($customer, $sku, $service, 10, '2026-04-01');
        Payment::query()->create([
            'order_id' => $pendingOrder->id,
            'payment_date' => '2026-04-09 09:00:00',
            'amount' => 999000,
            'status' => StatusApp::value('payment.status', 'pending'),
        ]);

        // Giá catalog thay đổi không được làm sai snapshot đã thu tiền.
        $service->forceFill(['unit_price' => 999000])->save();

        DB::enableQueryLog();
        $series = app(MonthlyRevenueAnalytics::class)->trailingMonths(2);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(2, $queryCount);
        $this->assertSame(['03/2026', '04/2026'], $series['labels']);
        $this->assertSame([250000.0, 500000.0], $series['revenue']);
        $this->assertSame([50000.0, 100000.0], $series['cup_printing_revenue']);
        $this->assertSame([2, 4], $series['cup_printing_quantity']);
        $this->assertSame([
            'label' => '04/2026',
            'revenue' => 500000.0,
            'cup_printing_revenue' => 100000.0,
            'cup_printing_quantity' => 4,
        ], app(MonthlyRevenueAnalytics::class)->currentMonth());
    }

    public function test_dashboard_registers_stats_and_two_adjacent_bar_charts(): void
    {
        CarbonImmutable::setTestNow('2026-04-15 10:00:00');
        $user = User::factory()->create();
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertContains(MonthlyRevenueStats::class, $widgets);
        $this->assertContains(TotalRevenueChart::class, $widgets);
        $this->assertContains(CupPrintingRevenueChart::class, $widgets);
        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
        $this->assertSame(1, app(TotalRevenueChart::class)->getColumnSpan());
        $this->assertSame(1, app(CupPrintingRevenueChart::class)->getColumnSpan());

        Livewire::actingAs($user)
            ->test(MonthlyRevenueStats::class)
            ->assertSee('Doanh thu tháng 04/2026')
            ->assertSee('Số lượng dịch vụ in ly tháng 04/2026');

        Livewire::actingAs($user)
            ->test(TotalRevenueChart::class)
            ->assertSee('Tổng doanh thu theo tháng')
            ->assertSee('Khoảng thời gian')
            ->assertSet('filters.months', '6');

        Livewire::actingAs($user)
            ->test(CupPrintingRevenueChart::class)
            ->assertSee('Doanh thu dịch vụ in ly theo tháng')
            ->assertSee('Khoảng thời gian')
            ->assertSet('filters.months', '6');
    }

    /** @return array{Customer, ProductSku, Service} */
    private function createCatalog(): array
    {
        $customer = Customer::query()->create([
            'name' => 'Khách báo cáo',
            'phone' => '0900000077',
        ]);
        $product = Product::query()->create([
            'name' => 'Ly báo cáo',
            'product_type' => 'in_ly',
            'unit' => 'cái',
        ]);
        $sku = ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'REPORT-SKU',
            'price' => 100000,
            'stock' => 100,
            'status' => 'active',
        ]);
        $service = Service::query()->where('code', Service::CUP_PRINTING_CODE)->firstOrFail();
        $service->forceFill(['unit_price' => 25000])->save();

        return [$customer, $sku, $service];
    }

    private function createSingleOrderWithService(
        Customer $customer,
        ProductSku $sku,
        Service $service,
        int $quantity,
        string $orderDate,
    ): Order {
        $order = $this->createOrder($customer, $orderDate, FulfillmentMode::Single);
        $item = $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => $quantity,
            'unit_price' => 100000,
            'subtotal' => $quantity * 100000,
        ]);
        $item->services()->create([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => 25000,
            'subtotal' => $quantity * 25000,
        ]);

        return $order;
    }

    private function createCustomerStockOrderWithPaidRelease(
        Customer $customer,
        ProductSku $sku,
        Service $service,
    ): Order {
        $order = $this->createOrder($customer, '2026-02-01', FulfillmentMode::CustomerStock);
        $orderItem = $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 4,
            'unit_price' => 100000,
            'subtotal' => 400000,
        ]);
        $orderItemService = $orderItem->services()->create([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'quantity' => 4,
            'unit_price' => 25000,
            'subtotal' => 100000,
        ]);
        $customerStock = CustomerStock::query()->create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'stocked_at' => '2026-02-10 09:00:00',
        ]);
        $customerStockItem = $customerStock->items()->create([
            'order_item_id' => $orderItem->id,
            'received_quantity' => 4,
            'released_quantity' => 1,
        ]);
        $release = StockRelease::query()->create([
            'uuid' => (string) Str::uuid(),
            'release_code' => 'REPORT-RELEASE',
            'customer_stock_id' => $customerStock->id,
            'released_at' => '2026-03-28 09:00:00',
            'gross_product_amount' => 100000,
            'gross_service_amount' => 25000,
            'allocated_discount' => 0,
            'reconciliation_adjustment' => 0,
            'total_amount' => 125000,
            'allocated_shipping_fee' => 0,
        ]);
        $releaseItem = StockReleaseItem::query()->create([
            'stock_release_id' => $release->id,
            'customer_stock_item_id' => $customerStockItem->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
        ]);
        $releaseItem->services()->create([
            'order_item_service_id' => $orderItemService->id,
            'service_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);

        return $order->load('customerStock.releases');
    }

    private function createOrder(Customer $customer, string $orderDate, FulfillmentMode $mode): Order
    {
        return Order::query()->create([
            'order_code' => 'REPORT-'.Str::random(12),
            'customer_id' => $customer->id,
            'order_date' => $orderDate,
            'status' => StatusApp::value('order.status', 'completed'),
            'fulfillment_mode' => $mode,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);
    }
}
