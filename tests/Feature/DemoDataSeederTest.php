<?php

namespace Tests\Feature;

use App\Models\CustomerStock;
use App\Models\InventoryMovement;
use App\Models\ManagedFile;
use App\Models\Order;
use App\Models\OrderInventoryAllocation;
use App\Models\ProductSku;
use App\Models\StockRelease;
use App\Models\StockReleaseItemService;
use App\Models\User;
use App\Support\StatusApp;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_complete_relational_data_for_modals(): void
    {
        $user = User::factory()->create();

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 20);
        $this->assertDatabaseCount('product', 20);
        $this->assertDatabaseCount('product_sku', 20);
        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('orders', 20);
        $this->assertDatabaseCount('order_item_services', 17);
        $this->assertDatabaseCount('customer_stock', 7);
        $this->assertDatabaseCount('stock_releases', 9);
        $this->assertGreaterThan(0, StockReleaseItemService::query()->count());
        $this->assertDatabaseCount('managed_files', 10);
        $this->assertDatabaseCount('attachments', 11);
        $this->assertSame(4, Order::query()->whereNotNull('closed_at')->count());
        $this->assertSame(1, ProductSku::query()->where('stock', 0)->count());
        $this->assertSame(1, ProductSku::query()->where('stock', 5)->count());
        $this->assertSame(
            5,
            OrderInventoryAllocation::query()
                ->where('status', StatusApp::value('inventory_allocation.status', 'allocated'))
                ->count(),
        );
        $this->assertGreaterThan(20, InventoryMovement::query()->count());
        $this->assertGreaterThan(20, Activity::query()->where('causer_id', $user->id)->count());

        $closedCustomerStock = CustomerStock::query()->whereNotNull('closed_at')->firstOrFail();
        $this->assertTrue($closedCustomerStock->items->every(
            fn ($item): bool => $item->released_quantity === $item->received_quantity,
        ));
        $this->assertTrue($closedCustomerStock->releases->every(
            fn (StockRelease $release): bool => $release->payment !== null && $release->shipping !== null,
        ));
        $this->assertSame(
            StatusApp::value('managed_file.status', 'failed'),
            ManagedFile::query()->whereNotNull('error_message')->sole()->status,
        );
    }
}
