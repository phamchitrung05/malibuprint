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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method', 30)
                ->default('standard')
                ->after('shipping_fee')
                ->comment('Phương thức vận chuyển của Order giao một lần');
            $table->string('shipping_tracking_code', 100)
                ->nullable()
                ->after('shipping_method')
                ->comment('Mã vận đơn Best Express');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_method', 'shipping_tracking_code']);
        });
    }
};
