<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sửa các khóa ngoại còn trỏ tới tên bảng cũ customer sau khi đổi sang customers.
        // MySQL chỉ cho phép khóa ngoại khi bảng cha dùng InnoDB.
        DB::statement('ALTER TABLE customers ENGINE=InnoDB');

        $ordersForeignKeyExists = DB::scalar("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'orders_customer_id_foreign'") > 0;
        Schema::table('orders', function (Blueprint $table) use ($ordersForeignKeyExists): void {
            if ($ordersForeignKeyExists) {
                $table->dropForeign(['customer_id']);
            }
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        $stockForeignKeyExists = DB::scalar("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_stock' AND CONSTRAINT_NAME = 'customer_stock_customer_id_foreign'") > 0;
        Schema::table('customer_stock', function (Blueprint $table) use ($stockForeignKeyExists): void {
            if ($stockForeignKeyExists) {
                $table->dropForeign(['customer_id']);
            }
            $table->foreign('customer_id')->references('id')->on('customers');
        });
    }

    public function down(): void
    {
        // Khóa ngoại được khôi phục về bảng cũ chỉ khi bảng customer tồn tại.
        if (! Schema::hasTable('customer')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customer');
        });

        Schema::table('customer_stock', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customer');
        });
    }
};
