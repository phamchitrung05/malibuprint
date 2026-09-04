<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_stock', function (Blueprint $table) {
            $table->comment('Hàng nguyên liệu/áo trắng của khách gửi kho xưởng in');

            $table->id();
            // Hàng tồn của khách phải tham chiếu đúng bảng customers.
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('product_sku_id')->constrained('product_sku');
            $table->integer('quantity')->default(0);
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_stock');
    }
};
