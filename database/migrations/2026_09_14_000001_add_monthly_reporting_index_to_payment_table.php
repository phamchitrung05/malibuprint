<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            // Dashboard luôn lọc Payment hoàn tất theo khoảng tháng nên index theo đúng thứ tự điều kiện.
            $table->index(['status', 'payment_date'], 'payment_status_payment_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropIndex('payment_status_payment_date_index');
        });
    }
};
