<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->comment('Catalog các dịch vụ có thể kèm theo sản phẩm trong Order');

            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->decimal('unit_price', 12, 2);
            $table->string('product_type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('order_item_services', function (Blueprint $table) {
            $table->comment('Snapshot dịch vụ được chọn cho từng dòng sản phẩm của Order');

            $table->id();
            $table->foreignId('order_item_id')->constrained('order_item')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->unique(['order_item_id', 'service_id']);
        });

        // Dịch vụ mặc định được tạo cùng schema để môi trường production dùng được ngay sau migrate.
        DB::table('services')->insert([
            'code' => 'in_ly',
            'name' => 'Dịch vụ in ly',
            'unit_price' => 170000,
            'product_type' => 'in_ly',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_services');
        Schema::dropIfExists('services');
    }
};
