<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mỗi ngày có một dòng sequence để hai người tạo đơn không nhận trùng số thứ tự.
        Schema::create('order_sequences', function (Blueprint $table): void {
            $table->id();
            $table->date('sequence_date')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};
