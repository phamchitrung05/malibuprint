<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            // Attachment chỉ là liên kết; metadata và file vật lý được quản lý tập trung ở managed_files.
            $table->id();
            $table->foreignId('managed_file_id')->constrained()->cascadeOnDelete();

            // Quan hệ polymorphic cho phép cùng cơ chế này dùng lại với Order, Product, Customer...
            $table->morphs('attachable');
            $table->string('collection', 50)->default('attachments');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('attached_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Ngăn một file bị gắn lặp lại vào cùng model và cùng nhóm tài liệu.
            $table->unique(
                ['managed_file_id', 'attachable_type', 'attachable_id', 'collection'],
                'attachments_unique_link',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
