<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->comment('Thanh toán / phiếu thu của đơn hàng');

            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->dateTime('payment_date')->useCurrent();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('completed');
            $table->text('note')->nullable();

            $table->foreignId('confirmed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
