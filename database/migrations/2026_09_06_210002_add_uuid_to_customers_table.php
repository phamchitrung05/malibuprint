<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // UUID là mã công khai ổn định của khách hàng, được form và Customer model sử dụng.
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
