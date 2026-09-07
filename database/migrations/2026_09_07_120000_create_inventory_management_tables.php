<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dữ liệu cũ cho phép null hoặc số âm; tồn khả dụng mới luôn phải là số nguyên không âm.
        DB::table('product_sku')->whereNull('stock')->update(['stock' => 0]);
        DB::table('product_sku')->where('stock', '<', 0)->update(['stock' => 0]);

        Schema::table('product_sku', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0)->nullable(false)->change();
        });

        Schema::create('order_inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_sku')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('status', 30)->default('allocated');
            // Version tăng sau mỗi lần thay đổi để tạo idempotency key không trùng với lần sửa hợp lệ tiếp theo.
            $table->unsignedInteger('version')->default(0);
            $table->dateTime('allocated_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'product_sku_id'], 'order_inventory_allocation_unique');
            $table->index(['product_sku_id', 'status'], 'inventory_allocation_sku_status_index');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->constrained('product_sku')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('order_inventory_allocations')->nullOnDelete();
            $table->string('type', 50);
            // Số âm là xuất khỏi tồn khả dụng, số dương là nhập hoặc hoàn lại tồn.
            $table->integer('quantity');
            $table->unsignedInteger('balance_before');
            $table->unsignedInteger('balance_after');
            $table->string('idempotency_key', 191)->unique();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_sku_id', 'created_at'], 'inventory_movement_sku_date_index');
            $table->index(['order_id', 'type'], 'inventory_movement_order_type_index');
        });

        // Giá trị stock tại thời điểm triển khai là số dư đầu kỳ; không replay Order lịch sử để tránh trừ hai lần.
        DB::table('product_sku')
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->each(function (object $sku): void {
                DB::table('inventory_movements')->insert([
                    'product_sku_id' => $sku->id,
                    'type' => 'opening_balance',
                    'quantity' => $sku->stock,
                    'balance_before' => 0,
                    'balance_after' => $sku->stock,
                    'idempotency_key' => "opening-balance:product-sku:{$sku->id}",
                    'reason' => 'Ghi nhận số dư tồn kho khi khởi tạo hệ thống quản lý tồn',
                    'created_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('order_inventory_allocations');

        Schema::table('product_sku', function (Blueprint $table) {
            $table->integer('stock')->nullable()->default(0)->change();
        });
    }
};
