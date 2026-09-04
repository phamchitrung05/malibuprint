<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Đổi tên bảng hiện tại để giữ nguyên dữ liệu khách hàng đã tồn tại.
        if (Schema::hasTable('customer') && ! Schema::hasTable('customers')) {
            Schema::rename('customer', 'customers');
        }
    }

    public function down(): void
    {
        // Cho phép rollback về tên bảng cũ khi cần quay lại phiên bản trước.
        if (Schema::hasTable('customers') && ! Schema::hasTable('customer')) {
            Schema::rename('customers', 'customer');
        }
    }
};
