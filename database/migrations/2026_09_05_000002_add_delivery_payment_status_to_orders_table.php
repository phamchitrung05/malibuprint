<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Giao hàng và thanh toán là hai việc độc lập nên được lưu thành hai cờ riêng.
            $table->boolean('is_delivered')->default(false)->after('status');
            $table->boolean('is_paid')->default(false)->after('is_delivered');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_delivered', 'is_paid']);
        });
    }
};
