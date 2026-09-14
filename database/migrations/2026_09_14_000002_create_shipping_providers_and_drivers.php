<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table): void {
            $table->id();
            // Tên là định danh hiển thị duy nhất theo nghiệp vụ; bảng không cần code/type phụ trợ.
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            // Tài xế là danh mục riêng để có thể tái sử dụng cho nhiều Order.
            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('license_plate', 30)->nullable();
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
            $table->foreignId('driver_id')
                ->nullable()
                ->after('shipping_provider_id')
                ->constrained('drivers')
                ->restrictOnDelete();
        });

        // Tạo dữ liệu mặc định để các Order cũ vẫn giữ được ngữ nghĩa vận chuyển sau khi đổi enum.
        $bestExpressId = DB::table('shipping_providers')->insertGetId([
            'name' => 'Best Express',
            'phone' => null,
            'is_active' => true,
            'note' => 'Đơn vị chuyển phát nhanh mặc định.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vehicleProviderId = DB::table('shipping_providers')->insertGetId([
            'name' => 'Giao hàng nội bộ',
            'phone' => null,
            'is_active' => true,
            'note' => 'Đơn vị giao hàng bằng xe nội bộ.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->where('shipping_method', 'best_express')->update([
            'shipping_method' => 'express',
            'shipping_provider_id' => $bestExpressId,
        ]);
        DB::table('orders')->where('shipping_method', 'standard')->update([
            'shipping_method' => 'vehicle',
            'shipping_provider_id' => $vehicleProviderId,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('shipping_provider_id');
        });

        Schema::dropIfExists('drivers');
        Schema::dropIfExists('shipping_providers');
    }
};
