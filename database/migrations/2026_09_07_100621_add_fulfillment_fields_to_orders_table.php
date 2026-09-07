<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tách hình thức giao hàng khỏi trạng thái sản xuất để tránh ghép nhiều khái niệm vào orders.status.
            $table->string('fulfillment_mode', 30)->default('single')->after('status');
            $table->string('fulfillment_status', 30)->default('pending')->after('fulfillment_mode');
            $table->dateTime('closed_at')->nullable()->after('fulfillment_status');
        });

        // Đồng bộ trạng thái các Order cũ đã giao để giao diện không hiển thị ngược với is_delivered/is_paid.
        DB::table('orders')->where('is_delivered', true)->update([
            'fulfillment_status' => 'fully_released',
        ]);
        DB::table('orders')->where('is_delivered', true)->where('is_paid', true)->update([
            'closed_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_mode', 'fulfillment_status', 'closed_at']);
        });
    }
};
