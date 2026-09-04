<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->comment('Đơn đặt hàng');

            $table->id();
            $table->string('order_code', 50)->unique();
            // Khóa ngoại trỏ tới bảng khách hàng số nhiều theo quy ước Laravel.
            $table->foreignId('customer_id')->constrained('customers');
            $table->dateTime('order_date')->useCurrent();
            $table->string('status', 30)->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
