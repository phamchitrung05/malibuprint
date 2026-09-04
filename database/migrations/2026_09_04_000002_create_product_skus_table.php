<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sku', function (Blueprint $table) {
            $table->comment('Biến thể SKU của sản phẩm (kích cỡ, màu, chất liệu)');

            $table->id();
            $table->foreignId('product_id')->constrained('product');
            $table->string('sku_code', 100)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->nullable()->default(0);
            $table->string('status', 20)->nullable()->default('active');

            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sku');
    }
};
