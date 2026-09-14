<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shipping_provider_id');
        });

        Schema::dropIfExists('shipping_providers');
    }

    public function down(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('shipping_provider_id')
                ->nullable()
                ->after('shipping_tracking_code')
                ->constrained('shipping_providers')
                ->restrictOnDelete();
        });

        $bestExpressId = DB::table('shipping_providers')->insertGetId([
            'name' => 'Best Express',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vehicleProviderId = DB::table('shipping_providers')->insertGetId([
            'name' => 'Giao hàng nội bộ',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->where('shipping_method', 'express')->update([
            'shipping_provider_id' => $bestExpressId,
        ]);
        DB::table('orders')->whereIn('shipping_method', ['vehicle', 'customer_pickup', 'inner_city'])->update([
            'shipping_provider_id' => $vehicleProviderId,
        ]);
    }
};
