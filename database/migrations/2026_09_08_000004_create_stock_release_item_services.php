<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_releases', function (Blueprint $table) {
            // Các khoản gross và discount được tách riêng để phiếu xuất có thể đối soát đầy đủ.
            $table->decimal('gross_product_amount', 15, 2)->default(0)->after('released_at');
            $table->decimal('gross_service_amount', 15, 2)->default(0)->after('gross_product_amount');
            $table->decimal('allocated_discount', 15, 2)->default(0)->after('gross_service_amount');
            $table->decimal('reconciliation_adjustment', 15, 2)->default(0)->after('allocated_discount');
        });

        Schema::create('stock_release_item_services', function (Blueprint $table) {
            $table->comment('Snapshot dịch vụ được phân bổ theo từng dòng sản phẩm của một lần xuất Customer Stock');

            $table->id();
            $table->foreignId('stock_release_item_id')->constrained('stock_release_items')->cascadeOnDelete();
            $table->foreignId('order_item_service_id')->constrained('order_item_services')->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->unique(
                ['stock_release_item_id', 'order_item_service_id'],
                'stock_release_item_service_unique',
            );
        });

        $this->backfillExistingReleases();
    }

    private function backfillExistingReleases(): void
    {
        $releaseItems = DB::table('stock_release_items')
            ->join('customer_stock_items', 'customer_stock_items.id', '=', 'stock_release_items.customer_stock_item_id')
            ->join('order_item', 'order_item.id', '=', 'customer_stock_items.order_item_id')
            ->get([
                'stock_release_items.id',
                'stock_release_items.stock_release_id',
                'stock_release_items.quantity',
                'stock_release_items.unit_price',
                'order_item.id as order_item_id',
            ]);

        foreach ($releaseItems as $releaseItem) {
            $serviceSnapshots = DB::table('order_item_services')
                ->where('order_item_id', $releaseItem->order_item_id)
                ->get();

            foreach ($serviceSnapshots as $serviceSnapshot) {
                DB::table('stock_release_item_services')->insertOrIgnore([
                    'stock_release_item_id' => $releaseItem->id,
                    'order_item_service_id' => $serviceSnapshot->id,
                    'service_name' => $serviceSnapshot->service_name,
                    'quantity' => $releaseItem->quantity,
                    'unit_price' => $serviceSnapshot->unit_price,
                    'subtotal' => (float) $serviceSnapshot->unit_price * (int) $releaseItem->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (DB::table('stock_releases')->get() as $release) {
            $grossProductAmount = (float) DB::table('stock_release_items')
                ->where('stock_release_id', $release->id)
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total')
                ->value('total');
            $grossServiceAmount = (float) DB::table('stock_release_item_services')
                ->join('stock_release_items', 'stock_release_items.id', '=', 'stock_release_item_services.stock_release_item_id')
                ->where('stock_release_items.stock_release_id', $release->id)
                ->sum('stock_release_item_services.subtotal');

            $order = DB::table('customer_stock')
                ->join('orders', 'orders.id', '=', 'customer_stock.order_id')
                ->where('customer_stock.id', $release->customer_stock_id)
                ->first(['orders.subtotal', 'orders.discount']);
            $grossAmount = $grossProductAmount + $grossServiceAmount;
            $ratio = $order && (float) $order->subtotal > 0 ? $grossAmount / (float) $order->subtotal : 0;
            $allocatedDiscount = $order ? round((float) $order->discount * $ratio, 2) : 0;
            // Chứng từ cũ giữ nguyên total; chênh lệch legacy được tách riêng, không ghi sai thành discount.
            $reconciliationAdjustment = round(
                (float) $release->total_amount
                    - ($grossAmount - $allocatedDiscount + (float) $release->allocated_shipping_fee),
                2,
            );

            DB::table('stock_releases')->where('id', $release->id)->update([
                'gross_product_amount' => $grossProductAmount,
                'gross_service_amount' => $grossServiceAmount,
                'allocated_discount' => $allocatedDiscount,
                'reconciliation_adjustment' => $reconciliationAdjustment,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_release_item_services');

        Schema::table('stock_releases', function (Blueprint $table) {
            $table->dropColumn([
                'gross_product_amount',
                'gross_service_amount',
                'allocated_discount',
                'reconciliation_adjustment',
            ]);
        });
    }
};
