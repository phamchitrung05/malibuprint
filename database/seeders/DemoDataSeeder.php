<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSequence;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const RECORD_COUNT = 20;

    /** @var list<string> */
    private const BUSINESS_TABLES = [
        'payment',
        'shipping',
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
        $this->clearBusinessData();

        // Mọi insert chạy trong một transaction để không để lại bộ dữ liệu dở dang nếu seed lỗi.
        DB::transaction(function (): void {
            $faker = fake('vi_VN');
            $userId = User::query()->value('id');
            $productNames = [
                'Card visit cao cấp', 'Tờ rơi A5', 'Poster A2', 'Menu nhà hàng', 'Hộp giấy mỹ phẩm',
                'Tem nhãn sản phẩm', 'Voucher giảm giá', 'Catalogue doanh nghiệp', 'Banner khai trương', 'Lịch để bàn',
                'Thiệp cưới', 'Túi giấy kraft', 'Phiếu bảo hành', 'Bìa hồ sơ', 'Nhãn chai nước',
                'Standee quảng cáo', 'Sổ tay doanh nghiệp', 'Phong bì thư', 'Giấy tiêu đề', 'Sticker chống nước',
            ];

            // Mỗi Product có đúng một SKU để bảng cha và bảng con đều có 20 record hợp lệ.
            $products = collect(range(1, self::RECORD_COUNT))->map(function (int $index) use ($faker, $productNames, $userId): Product {
                return Product::create([
                    'name' => $productNames[$index - 1],
                    'product_type' => $faker->randomElement(['in_ly', 'in_card', 'in_menu', 'in_hop', 'in_banner']),
                    'unit' => $faker->randomElement(['cái', 'bộ', 'tờ', 'cuốn']),
                    'is_active' => $index % 10 !== 0,
                    'note' => 'Sản phẩm mẫu số '.$index.' dùng để kiểm tra hệ thống.',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            });

            $skus = $products->values()->map(function (Product $product, int $offset) use ($faker): ProductSku {
                $index = $offset + 1;

                return ProductSku::create([
                    'product_id' => $product->id,
                    'sku_code' => 'MP-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'price' => $faker->numberBetween(5, 500) * 100,
                    'stock' => $faker->numberBetween(50, 2000),
                    'status' => $product->is_active ? 'active' : 'inactive',
                ]);
            });

            $customers = collect(range(1, self::RECORD_COUNT))->map(function (int $index) use ($faker): Customer {
                return Customer::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $faker->name(),
                    'phone' => $faker->unique()->numerify('09########'),
                    'address' => $faker->address(),
                    'note' => $index % 4 === 0 ? 'Khách hàng thường xuyên đặt sản phẩm in.' : null,
                    'is_active' => $index % 10 !== 0,
                    'last_order' => now()->subDays(self::RECORD_COUNT - $index),
                ]);
            });

            foreach (range(1, self::RECORD_COUNT) as $index) {
                $orderDate = now()->startOfDay()->subDays(self::RECORD_COUNT - $index)->addHours(8 + ($index % 9));
                $status = ['pending', 'processing', 'completed', 'completed', 'cancelled'][($index - 1) % 5];
                $isPaid = $status !== 'cancelled' && $index % 2 === 0;
                $isDelivered = $status !== 'cancelled' && $index % 3 === 0;
                $quantity = $faker->numberBetween(50, 1000);
                $unitPrice = (int) $skus[$index - 1]->price;
                $subtotal = $quantity * $unitPrice;
                $discount = $index % 4 === 0 ? (int) round($subtotal * 0.05) : 0;
                $total = $subtotal - $discount;

                // Mỗi ngày có một sequence và một Order để mã ORD-DDMMYY-001 luôn nhất quán.
                OrderSequence::create([
                    'sequence_date' => $orderDate->toDateString(),
                    'last_number' => 1,
                ]);

                $order = Order::create([
                    'order_code' => 'ORD-'.$orderDate->format('dmy').'-001',
                    'customer_id' => $customers[$index - 1]->id,
                    'order_date' => $orderDate,
                    'status' => $status,
                    'is_delivered' => $isDelivered,
                    'is_paid' => $isPaid,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total_amount' => $total,
                    'note' => $index % 3 === 0 ? "Kiểm tra kỹ file thiết kế.\nGiao hàng đúng thời gian." : null,
                    'created_by' => $userId,
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_sku_id' => $skus[$index - 1]->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_date' => $isPaid ? $orderDate->copy()->addDay() : $orderDate,
                    'amount' => $total,
                    'status' => $isPaid ? 'completed' : 'pending',
                    'note' => $isPaid ? 'Đã thu đủ tiền đơn hàng.' : 'Đang chờ khách hàng thanh toán.',
                    'confirmed_by' => $isPaid ? $userId : null,
                ]);

                $shippingStatus = match (true) {
                    $isDelivered => 'delivered',
                    $status === 'completed' => 'shipping',
                    default => 'pending',
                };
                $shippedAt = $shippingStatus === 'pending' ? null : $orderDate->copy()->addDays(2);

                Shipping::create([
                    'order_id' => $order->id,
                    'status' => $shippingStatus,
                    'shipped_at' => $shippedAt,
                    'delivered_at' => $isDelivered ? $shippedAt?->copy()->addDay() : null,
                    'confirmed_by' => $isDelivered ? $userId : null,
                ]);

                CustomerStock::create([
                    'customer_id' => $customers[$index - 1]->id,
                    'product_sku_id' => $skus[$index - 1]->id,
                    'quantity' => $faker->numberBetween(0, 500),
                    'note' => 'Tồn kho của khách hàng cho sản phẩm '.$products[$index - 1]->name.'.',
                ]);
            }
        });
    }

    /**
     * Xóa dữ liệu con trước dữ liệu cha và tuyệt đối không tác động đến bảng users.
     */
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
