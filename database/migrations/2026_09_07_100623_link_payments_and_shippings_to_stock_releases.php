<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            // Payment chỉ tồn tại sau khi admin xác nhận; unique bảo đảm một phiếu xuất không bị thu tiền hai lần.
            $table->foreignId('stock_release_id')->nullable()->unique()->constrained('stock_releases');
        });

        Schema::table('shipping', function (Blueprint $table) {
            // Mỗi phiếu xuất tương ứng đúng một lần giao hàng đã được xác nhận tại xưởng.
            $table->foreignId('stock_release_id')->nullable()->unique()->constrained('stock_releases');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_release_id');
        });

        Schema::table('shipping', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_release_id');
        });
    }
};
