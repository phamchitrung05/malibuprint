<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Shipping;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('vi_VN');

        // Tạo 20 sản phẩm gốc để làm dữ liệu cha cho các SKU.
        $products = collect(range(1, 20))->map(fn (int $index) => Product::create([
            'name' => 'Sản phẩm in mẫu '.$index,
            'product_type' => $faker->randomElement(['in_card', 'in_menu', 'in_hop', 'in_banner']),
            'unit' => $faker->randomElement(['cái', 'bộ', 'tờ']),
            'is_active' => true,
            'note' => 'Dữ liệu mẫu dùng cho giao diện quản trị.',
        ]));

        // Mỗi SKU liên kết với một sản phẩm để kiểm tra quan hệ Product - ProductSku.
        $skus = collect(range(1, 20))->map(fn (int $index) => ProductSku::create([
            'product_id' => $products[$index - 1]->id,
            'sku_code' => 'SKU-DEMO-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'price' => $faker->randomFloat(2, 100, 5000),
            'stock' => $faker->numberBetween(10, 1000),
            'status' => 'active',
        ]));

        // Tạo 20 khách hàng độc lập, không xóa dữ liệu khách hàng đang có trong database.
        $customers = collect(range(1, 20))->map(fn (int $index) => Customer::create([
            'uuid' => (string) Str::uuid(),
            'name' => $faker->name(),
            'phone' => $faker->numerify('09########'),
            'address' => $faker->address(),
            'note' => 'Khách hàng mẫu số '.$index,
            'is_active' => true,
            'last_order' => now()->subDays($index),
        ]));

        // Mỗi đơn hàng được gắn một khách hàng và sinh số tiền để hiển thị trên bảng quản trị.
        $orders = collect(range(1, 20))->map(function (int $index) use ($faker, $customers) {
            $subtotal = $faker->randomFloat(2, 200000, 8000000);
            $discount = $faker->randomFloat(2, 0, 200000);

            return Order::create([
                'order_code' => 'DEMO-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                'customer_id' => $customers[$index - 1]->id,
                'order_date' => now()->subDays($index),
                'status' => $faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => max(0, $subtotal - $discount),
                'note' => 'Đơn hàng mẫu dùng để kiểm tra giao diện.',
            ]);
        });

        // Tạo đúng 20 dòng sản phẩm, mỗi dòng trỏ đến một đơn hàng và một SKU hợp lệ.
        $orderItems = collect(range(1, 20))->map(fn (int $index) => OrderItem::create([
            'order_id' => $orders[$index - 1]->id,
            'product_sku_id' => $skus[$index - 1]->id,
            'quantity' => $faker->numberBetween(1, 1000),
            'unit_price' => $faker->randomFloat(2, 100, 5000),
            'subtotal' => $faker->randomFloat(2, 200000, 5000000),
        ]));

        // Sinh 20 phiếu thanh toán và 20 thông tin giao hàng cho các đơn hàng mẫu.
        collect(range(1, 20))->each(fn (int $index) => Payment::create([
            'order_id' => $orders[$index - 1]->id,
            'payment_date' => now()->subDays($index - 1),
            'amount' => $orders[$index - 1]->total_amount,
            'status' => 'completed',
            'note' => 'Thanh toán mẫu.',
        ]));

        collect(range(1, 20))->each(fn (int $index) => Shipping::create([
            'order_id' => $orders[$index - 1]->id,
            'status' => $faker->randomElement(['pending', 'shipping', 'delivered']),
            'shipped_at' => now()->subDays(max(0, $index - 2)),
            'delivered_at' => $index % 3 === 0 ? now()->subDay() : null,
        ]));

        // Tạo 20 bản ghi tồn kho khách hàng, dùng lại các khóa ngoại đã tạo ở trên.
        collect(range(1, 20))->each(fn (int $index) => CustomerStock::create([
            'customer_id' => $customers[$index - 1]->id,
            'product_sku_id' => $skus[$index - 1]->id,
            'quantity' => $faker->numberBetween(0, 500),
            'note' => 'Tồn kho mẫu của khách hàng.',
        ]));
    }
}
