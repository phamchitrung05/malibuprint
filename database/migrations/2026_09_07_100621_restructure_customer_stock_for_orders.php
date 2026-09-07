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
        Schema::table('customer_stock', function (Blueprint $table) {
            // order_id là nullable để giữ lại an toàn các dòng tồn kho legacy chưa xác định được Order nguồn.
            $table->foreignId('order_id')->nullable()->after('customer_id')->unique()->constrained('orders');
            $table->dateTime('stocked_at')->nullable()->after('note');
            $table->dateTime('closed_at')->nullable()->after('stocked_at');
            $table->foreignId('created_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();

            // Hai cột legacy được giữ lại trong giai đoạn chuyển đổi nhưng không còn là nguồn dữ liệu tồn kho mới.
            $table->foreignId('product_sku_id')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_stock', function (Blueprint $table) {
            // Không ép hai cột legacy về NOT NULL vì record mới không còn ghi dữ liệu vào các cột này.
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['stocked_at', 'closed_at']);
        });
    }
};
