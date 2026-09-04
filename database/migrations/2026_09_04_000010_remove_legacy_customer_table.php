<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng cũ đang rỗng; xóa nó để hệ thống chỉ còn dùng bảng customers.
        if (Schema::hasTable('customer') && Schema::hasTable('customers')) {
            Schema::disableForeignKeyConstraints();
            Schema::drop('customer');
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Không khôi phục bảng cũ vì migration tạo customers là nguồn dữ liệu chuẩn.
    }
};
