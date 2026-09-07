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
        Schema::create('stock_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_release_id')->constrained('stock_releases')->cascadeOnDelete();
            $table->foreignId('customer_stock_item_id')->constrained('customer_stock_items');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['stock_release_id', 'customer_stock_item_id'], 'stock_release_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_release_items');
    }
};
