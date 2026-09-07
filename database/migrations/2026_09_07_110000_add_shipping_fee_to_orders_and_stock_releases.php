<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_fee', 15, 2)
                ->default(0)
                ->after('discount')
                ->comment('Phí giao hàng cố định của toàn đơn hàng');
        });

        Schema::table('stock_releases', function (Blueprint $table) {
            $table->decimal('allocated_shipping_fee', 15, 2)
                ->default(0)
                ->after('total_amount')
                ->comment('Phần phí giao hàng của đơn được hệ thống phân bổ cho phiếu xuất');
        });
    }

    public function down(): void
    {
        Schema::table('stock_releases', function (Blueprint $table) {
            $table->dropColumn('allocated_shipping_fee');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_fee');
        });
    }
};
