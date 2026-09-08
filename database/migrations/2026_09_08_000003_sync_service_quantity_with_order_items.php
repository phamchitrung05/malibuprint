<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $snapshots = DB::table('order_item_services')
            ->join('order_item', 'order_item.id', '=', 'order_item_services.order_item_id')
            ->get([
                'order_item_services.id',
                'order_item_services.unit_price',
                'order_item.quantity as product_quantity',
                'order_item.order_id',
            ]);

        foreach ($snapshots as $snapshot) {
            // Một lượt dịch vụ dùng quantity của chính Order Item để tính chi phí in cho từng sản phẩm.
            DB::table('order_item_services')
                ->where('id', $snapshot->id)
                ->update([
                    'quantity' => $snapshot->product_quantity,
                    'subtotal' => (float) $snapshot->unit_price * (int) $snapshot->product_quantity,
                    'updated_at' => now(),
                ]);
        }

        foreach ($snapshots->pluck('order_id')->unique() as $orderId) {
            $productSubtotal = (float) DB::table('order_item')
                ->where('order_id', $orderId)
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total')
                ->value('total');
            $serviceSubtotal = (float) DB::table('order_item_services')
                ->join('order_item', 'order_item.id', '=', 'order_item_services.order_item_id')
                ->where('order_item.order_id', $orderId)
                ->sum('order_item_services.subtotal');
            $order = DB::table('orders')->where('id', $orderId)->first(['discount', 'shipping_fee']);

            if ($order === null) {
                continue;
            }

            $subtotal = $productSubtotal + $serviceSubtotal;

            DB::table('orders')->where('id', $orderId)->update([
                'subtotal' => $subtotal,
                'total_amount' => max(0, $subtotal - (float) $order->discount + (float) $order->shipping_fee),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Đây là migration dữ liệu theo quy tắc nghiệp vụ mới nên không tự suy diễn quantity khi rollback.
    }
};
