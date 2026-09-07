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
        Schema::create('customer_stock_items', function (Blueprint $table) {
            $table->id();
            // Mỗi Order Item chỉ được nhập vào đúng một lô Customer Stock để tránh cộng tồn hai lần.
            $table->foreignId('customer_stock_id')->constrained('customer_stock')->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_item');
            $table->unsignedInteger('received_quantity');
            $table->unsignedInteger('released_quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_stock_items');
    }
};
