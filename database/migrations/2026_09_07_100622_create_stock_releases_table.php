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
        Schema::create('stock_releases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('release_code', 80)->unique();
            $table->foreignId('customer_stock_id')->constrained('customer_stock');
            $table->dateTime('released_at');
            // Tổng tiền được chốt trên phiếu để lịch sử không thay đổi khi Order được chỉnh sửa về sau.
            $table->decimal('total_amount', 12, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_releases');
    }
};
