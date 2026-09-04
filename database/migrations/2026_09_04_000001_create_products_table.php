<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->comment('Sản phẩm gốc (mẫu in ấn)');

            $table->id();
            $table->string('name');
            $table->string('product_type', 50)->default('in_ly');
            $table->string('unit', 50);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
