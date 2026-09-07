<?php

namespace Database\Seeders;

use App\Enums\FulfillmentMode;
use App\Models\Customer;
use App\Models\ManagedFile;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\AttachmentManager;
use App\Services\CustomerStockManager;
use App\Services\InventoryManager;
use App\Services\OrderActivityLogger;
use App\Services\OrderCodeService;
use App\Services\OrderInventoryManager;
use App\Services\PaymentManager;
use App\Services\ShippingManager;
use App\Services\StockReleaseManager;
use App\Support\StatusApp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const RECORD_COUNT = 20;

    /**
     * Seeder demo chủ động làm sạch toàn bộ dữ liệu nghiệp vụ nhưng luôn giữ nguyên bảng users.
     * Thứ tự từ bảng con đến bảng cha giúp chạy lặp lại mà không để lại liên kết mồ côi.
     *
     * @var list<string>
     */
    private const BUSINESS_TABLES = [
        'attachments',
        'managed_files',
        'activity_log',
        'inventory_movements',
        'order_inventory_allocations',
        'payment',
        'shipping',
        'stock_release_items',
        'stock_releases',
        'customer_stock_items',
        'customer_stock',
        'order_item',
        'orders',
        'product_sku',
        'product',
        'customers',
        'order_sequences',
    ];

    public function run(): void
    {
        $actor = User::query()->oldest('id')->first()
            ?? User::query()->create([
                'name' => 'Quản trị demo',
                'email' => 'demo@example.com',
                'password' => 'password',
            ]);

        $this->clearBusinessData();
        Auth::login($actor);

        try {
            DB::transaction(function () use ($actor): void {
                $faker = fake('vi_VN');
                $faker->seed(20260907);
                $products = $this->createProducts($faker, $actor->id);
                $skus = $this->createSkus($products, $faker);
                $customers = $this->createCustomers($faker);
                $orders = $this->createOrders($customers, $skus, $actor->id);

                $this->createDemoAttachments($orders, $actor->id);
                $this->createInventoryLevelExamples($skus, $actor->id);
            });
        } finally {
            Auth::logout();
        }
    }

    /** @return Collection<int, Product> */
    private function createProducts(object $faker, int $actorId): Collection
    {
        $productNames = [
            'Card visit cao cấp', 'Tờ rơi A5', 'Poster A2', 'Menu nhà hàng', 'Hộp giấy mỹ phẩm',
            'Tem nhãn sản phẩm', 'Voucher giảm giá', 'Catalogue doanh nghiệp', 'Banner khai trương', 'Lịch để bàn',
            'Thiệp cưới', 'Túi giấy kraft', 'Phiếu bảo hành', 'Bìa hồ sơ', 'Nhãn chai nước',
            'Standee quảng cáo', 'Sổ tay doanh nghiệp', 'Phong bì thư', 'Giấy tiêu đề', 'Sticker chống nước',
        ];

        return collect($productNames)->map(function (string $name, int $offset) use ($faker, $actorId): Product {
            $index = $offset + 1;

            return Product::query()->create([
                'name' => $name,
                'product_type' => $faker->randomElement(['in_ly', 'in_card', 'in_menu', 'in_hop', 'in_banner']),
                'unit' => $faker->randomElement(['cái', 'bộ', 'tờ', 'cuốn']),
                'is_active' => $index <= 18,
                'note' => "Sản phẩm demo số {$index}, dùng kiểm tra form và modal quản trị.",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        });
    }

    /** @param Collection<int, Product> $products
     * @return Collection<int, ProductSku>
     */
    private function createSkus(Collection $products, object $faker): Collection
    {
        return $products->values()->map(function (Product $product, int $offset) use ($faker): ProductSku {
            $index = $offset + 1;

            return ProductSku::query()->create([
                'product_id' => $product->id,
                'sku_code' => 'MP-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'price' => $faker->numberBetween(5, 500) * 100,
                // Tồn đầu kỳ đủ lớn để mọi scenario Order có thể cấp tồn trong cùng transaction.
                'stock' => 5000 + ($index * 100),
                'status' => StatusApp::value('product_sku.status', 'active'),
            ]);
        });
    }

    /** @return Collection<int, Customer> */
    private function createCustomers(object $faker): Collection
    {
        return collect(range(1, self::RECORD_COUNT))->map(function (int $index) use ($faker): Customer {
            return Customer::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $faker->name(),
                'phone' => $faker->unique()->numerify('09########'),
                'address' => $faker->address(),
                'note' => $index % 4 === 0 ? 'Khách hàng demo thường xuyên đặt sản phẩm in.' : null,
                'is_active' => $index <= 18,
            ]);
        });
    }

    /**
     * Mỗi scenario đại diện cho một tổ hợp mà modal cần hiển thị: Order đang mở, đã hủy,
     * single thanh toán/giao độc lập và Customer Stock xuất một phần hoặc toàn bộ.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, ProductSku>  $skus
     * @return Collection<int, Order>
     */
    private function createOrders(Collection $customers, Collection $skus, int $actorId): Collection
    {
        return collect($this->orderScenarios())->map(function (array $scenario, int $offset) use ($customers, $skus, $actorId): Order {
            $index = $offset + 1;
            $orderDate = now()->startOfDay()->subDays(self::RECORD_COUNT - $index)->addHours(8 + ($index % 9));
            $mode = FulfillmentMode::from($scenario['mode']);
            $order = Order::query()->create([
                'order_code' => app(OrderCodeService::class)->generate($orderDate),
                'customer_id' => $customers[$offset]->id,
                'order_date' => $orderDate,
                'delivery_date' => $orderDate->copy()->addDays(3 + ($index % 5)),
                'status' => StatusApp::default('order.status'),
                'fulfillment_mode' => $mode,
                'fulfillment_status' => StatusApp::default('order.fulfillment_status'),
                'subtotal' => 0,
                'discount' => $index % 4 === 0 ? 50000 : 0,
                'shipping_fee' => 15000 + ($index * 1000),
                'total_amount' => 0,
                'note' => "Order demo scenario {$index}: {$scenario['description']}",
                'created_by' => $actorId,
            ]);

            $itemCount = $index % 3 === 0 ? 2 : 1;

            foreach (range(0, $itemCount - 1) as $itemOffset) {
                $sku = $skus[($offset + $itemOffset) % $skus->count()];
                $quantity = 10 + (($index + $itemOffset) % 15);

                $order->items()->create([
                    'product_sku_id' => $sku->id,
                    'quantity' => $quantity,
                    'unit_price' => $sku->price,
                    'subtotal' => $quantity * (float) $sku->price,
                ]);
            }

            $order->recalculateTotals();
            app(OrderInventoryManager::class)->syncForOrder($order->id, $actorId);

            // Order đầu tiên được tăng rồi giảm để lịch sử tồn có đủ movement chỉnh sửa số lượng.
            if ($index === 1) {
                $item = $order->items()->firstOrFail();
                $item->increment('quantity', 3);
                app(OrderInventoryManager::class)->syncForOrder($order->id, $actorId);
                $item->decrement('quantity', 1);
                app(OrderInventoryManager::class)->syncForOrder($order->id, $actorId);
                $order->recalculateTotals();
            }

            $this->applyOrderScenario($order, $scenario, $actorId);

            return $order->refresh();
        });
    }

    /** @param array<string, mixed> $scenario */
    private function applyOrderScenario(Order $order, array $scenario, int $actorId): void
    {
        $targetStatus = $scenario['status'];

        if ($targetStatus === StatusApp::value('order.status', 'pending')) {
            return;
        }

        $this->markProcessing($order);

        if ($targetStatus === StatusApp::value('order.status', 'processing')) {
            return;
        }

        if ($targetStatus === StatusApp::value('order.status', 'cancelled')) {
            app(OrderInventoryManager::class)->releaseForCancelledOrder($order, $actorId);
            $order->forceFill(['status' => $targetStatus])->saveQuietly();
            app(OrderActivityLogger::class)->log($order, 'order.cancelled', 'Đã hủy đơn hàng demo', [
                'old' => ['status' => StatusApp::value('order.status', 'processing')],
                'new' => ['status' => $targetStatus],
            ]);

            return;
        }

        app(OrderInventoryManager::class)->consumeForCompletedOrder($order);
        $order->forceFill(['status' => StatusApp::value('order.status', 'completed')])->saveQuietly();
        app(OrderActivityLogger::class)->log($order, 'order.status_changed', 'Đã hoàn thành sản xuất demo', [
            'old' => ['status' => StatusApp::value('order.status', 'processing')],
            'new' => ['status' => StatusApp::value('order.status', 'completed')],
        ]);

        if ($order->fulfillment_mode === FulfillmentMode::CustomerStock) {
            $this->applyCustomerStockScenario($order, $scenario, $actorId);
        } else {
            $this->applySingleOrderScenario($order, $scenario, $actorId);
        }
    }

    private function markProcessing(Order $order): void
    {
        $oldStatus = $order->status;
        $processing = StatusApp::value('order.status', 'processing');
        $order->forceFill(['status' => $processing])->saveQuietly();
        app(OrderActivityLogger::class)->log($order, 'order.status_changed', 'Đã bắt đầu xử lý Order demo', [
            'old' => ['status' => $oldStatus],
            'new' => ['status' => $processing],
        ]);
    }

    /** @param array<string, mixed> $scenario */
    private function applySingleOrderScenario(Order $order, array $scenario, int $actorId): void
    {
        if ($scenario['legacy_payment'] ?? null) {
            $order->payments()->create([
                'payment_date' => now(),
                'amount' => 0,
                'status' => $scenario['legacy_payment'],
                'note' => 'Chứng từ demo để hiển thị trạng thái thanh toán.',
                'confirmed_by' => $actorId,
            ]);
        }

        if ($scenario['legacy_shipping'] ?? null) {
            $isShipping = $scenario['legacy_shipping'] === StatusApp::value('shipping.status', 'shipping');
            $order->shipping()->create([
                'status' => $scenario['legacy_shipping'],
                'shipped_at' => $isShipping ? now() : null,
                'confirmed_by' => $actorId,
            ]);
        }

        if ($scenario['paid']) {
            app(PaymentManager::class)->confirmSingleOrder($order->id, 'Đã thu đủ tiền Order demo.', $actorId);
        }

        if ($scenario['delivered']) {
            app(ShippingManager::class)->confirmSingleOrder($order->id, $actorId);
        }
    }

    /** @param array<string, mixed> $scenario */
    private function applyCustomerStockScenario(Order $order, array $scenario, int $actorId): void
    {
        $customerStock = app(CustomerStockManager::class)->createForCompletedOrder($order->id, $actorId);
        $releaseMode = $scenario['release'];

        if ($releaseMode === 'none' || ! $customerStock) {
            return;
        }

        $firstQuantities = $customerStock->items()->get()->mapWithKeys(
            fn ($item): array => [$item->id => max(1, (int) floor($item->received_quantity / 2))],
        )->all();
        $firstRelease = app(StockReleaseManager::class)->release(
            $customerStock->id,
            $firstQuantities,
            'Phiếu xuất demo đợt 1.',
            $actorId,
        );

        if (($scenario['paid_releases'] ?? 0) >= 1) {
            app(PaymentManager::class)->confirmStockRelease(
                $customerStock->id,
                $firstRelease->id,
                'Đã thu tiền phiếu xuất demo đợt 1.',
                $actorId,
            );
        }

        if ($releaseMode !== 'full') {
            return;
        }

        $remainingQuantities = $customerStock->items()->get()->mapWithKeys(
            fn ($item): array => [$item->id => $item->remainingQuantity()],
        )->filter()->all();
        $secondRelease = app(StockReleaseManager::class)->release(
            $customerStock->id,
            $remainingQuantities,
            'Phiếu xuất demo đợt cuối.',
            $actorId,
        );

        if (($scenario['paid_releases'] ?? 0) >= 2) {
            app(PaymentManager::class)->confirmStockRelease(
                $customerStock->id,
                $secondRelease->id,
                'Đã thu tiền phiếu xuất demo đợt cuối.',
                $actorId,
            );
        }
    }

    /**
     * Tạo file metadata demo, không tạo file vật lý và không dispatch queue Google Drive.
     *
     * @param  Collection<int, Order>  $orders
     */
    private function createDemoAttachments(Collection $orders, int $actorId): void
    {
        $sharedFile = $this->createReadyDemoFile(1, $actorId, 'bang-mau-in-chung.pdf');
        app(AttachmentManager::class)->attachExisting($orders[0], $sharedFile, userId: $actorId);
        app(AttachmentManager::class)->attachExisting($orders[8], $sharedFile, userId: $actorId);

        foreach ($orders->take(8)->values() as $offset => $order) {
            $file = $this->createReadyDemoFile($offset + 2, $actorId, "thiet-ke-order-{$order->order_code}.pdf");
            app(AttachmentManager::class)->attachExisting($order, $file, userId: $actorId);
        }

        $failedFile = ManagedFile::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'original_name' => 'file-demo-upload-loi.ai',
            'storage_name' => 'file-demo-upload-loi.ai',
            'path' => 'demo/file-demo-upload-loi.ai',
            'mime_type' => 'application/postscript',
            'extension' => 'ai',
            'size' => 2_400_000,
            'status' => StatusApp::value('managed_file.status', 'failed'),
            'error_message' => 'Lỗi demo: chưa thể kết nối Google Drive.',
            'uploaded_by' => $actorId,
        ]);
        $orders[4]->attachments()->create([
            'managed_file_id' => $failedFile->id,
            'collection' => 'attachments',
            'sort_order' => 1,
            'attached_by' => $actorId,
        ]);
    }

    private function createReadyDemoFile(int $sequence, int $actorId, string $name): ManagedFile
    {
        $driveId = 'demo-drive-file-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        return ManagedFile::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'drive_file_id' => $driveId,
            'original_name' => $name,
            'storage_name' => $name,
            'path' => "demo/{$name}",
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 350_000 + ($sequence * 25_000),
            'status' => StatusApp::value('managed_file.status', 'ready'),
            'web_view_link' => "https://drive.google.com/file/d/{$driveId}/view",
            'uploaded_by' => $actorId,
            'uploaded_at' => now(),
        ]);
    }

    /** @param Collection<int, ProductSku> $skus */
    private function createInventoryLevelExamples(Collection $skus, int $actorId): void
    {
        app(InventoryManager::class)->adjust($skus[0]->id, 50, 'Nhập bổ sung tồn demo.', $actorId);

        foreach ([18 => 5, 19 => 0] as $offset => $targetStock) {
            $sku = $skus[$offset]->refresh();
            $difference = $targetStock - $sku->stock;

            if ($difference !== 0) {
                app(InventoryManager::class)->adjust(
                    $sku->id,
                    $difference,
                    'Điều chỉnh để minh họa cảnh báo tồn kho demo.',
                    $actorId,
                );
            }

            $sku->forceFill([
                'status' => StatusApp::value('product_sku.status', 'inactive'),
            ])->saveQuietly();
        }
    }

    /** @return list<array<string, mixed>> */
    private function orderScenarios(): array
    {
        $pending = StatusApp::value('order.status', 'pending');
        $processing = StatusApp::value('order.status', 'processing');
        $completed = StatusApp::value('order.status', 'completed');
        $cancelled = StatusApp::value('order.status', 'cancelled');
        $single = FulfillmentMode::Single->value;
        $customerStock = FulfillmentMode::CustomerStock->value;

        return [
            ['status' => $pending, 'mode' => $single, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Single mới tạo, đang giữ tồn'],
            ['status' => $processing, 'mode' => $single, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Single đang sản xuất'],
            ['status' => $cancelled, 'mode' => $single, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Single đã hủy và hoàn tồn'],
            ['status' => $cancelled, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Customer Stock đã hủy và hoàn tồn'],
            ['status' => $completed, 'mode' => $single, 'paid' => false, 'delivered' => false, 'legacy_payment' => StatusApp::value('payment.status', 'pending'), 'release' => 'none', 'description' => 'Single hoàn thành, payment chờ'],
            ['status' => $completed, 'mode' => $single, 'paid' => true, 'delivered' => false, 'legacy_shipping' => StatusApp::value('shipping.status', 'shipping'), 'release' => 'none', 'description' => 'Single đã thu tiền, đang giao'],
            ['status' => $completed, 'mode' => $single, 'paid' => false, 'delivered' => true, 'release' => 'none', 'description' => 'Single đã giao, chưa thu tiền'],
            ['status' => $completed, 'mode' => $single, 'paid' => true, 'delivered' => true, 'release' => 'none', 'description' => 'Single đã giao và thanh toán, đã kết thúc'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Customer Stock sẵn sàng, chưa xuất'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'partial', 'paid_releases' => 0, 'description' => 'Customer Stock đã xuất một phần, chưa thu'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'partial', 'paid_releases' => 1, 'description' => 'Customer Stock xuất một phần, đã thu phiếu đầu'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'full', 'paid_releases' => 0, 'description' => 'Customer Stock xuất hết, chưa thu tiền'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'full', 'paid_releases' => 2, 'description' => 'Customer Stock xuất hết, thu đủ và đóng kho'],
            ['status' => $pending, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Customer Stock mới tạo, chưa sản xuất'],
            ['status' => $processing, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Customer Stock đang sản xuất'],
            ['status' => $cancelled, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'none', 'description' => 'Customer Stock hủy khi đang xử lý'],
            ['status' => $completed, 'mode' => $single, 'paid' => true, 'delivered' => true, 'release' => 'none', 'description' => 'Single hoàn tất toàn bộ'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'partial', 'paid_releases' => 1, 'description' => 'Customer Stock nhiều SKU, xuất và thu một phần'],
            ['status' => $completed, 'mode' => $customerStock, 'paid' => false, 'delivered' => false, 'release' => 'full', 'paid_releases' => 2, 'description' => 'Customer Stock nhiều SKU đã tất toán'],
            ['status' => $completed, 'mode' => $single, 'paid' => false, 'delivered' => false, 'legacy_payment' => StatusApp::value('payment.status', 'cancelled'), 'legacy_shipping' => StatusApp::value('shipping.status', 'pending'), 'release' => 'none', 'description' => 'Single có chứng từ hủy và giao hàng chờ'],
        ];
    }

    private function clearBusinessData(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::BUSINESS_TABLES as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
