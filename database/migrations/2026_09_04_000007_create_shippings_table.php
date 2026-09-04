<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping', function (Blueprint $table) {
            $table->comment('Thông tin giao hàng của đơn hàng');

            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('status', 30)->default('pending');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();

            $table->foreignId('confirmed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping');
    }
};
