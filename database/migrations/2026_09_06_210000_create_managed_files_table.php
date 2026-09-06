<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_files', function (Blueprint $table): void {
            // Một record đại diện cho đúng một file vật lý, độc lập với model đang sử dụng file đó.
            $table->id();
            $table->uuid('uuid')->unique();

            // Thông tin file đích trên Google Drive; drive_file_id chỉ có sau khi queue upload thành công.
            $table->string('disk')->default('google');
            $table->string('drive_file_id')->nullable()->unique();
            $table->string('original_name');
            $table->string('storage_name');
            $table->string('path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 30)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();

            // Trạng thái giúp giao diện theo dõi file đang chờ, đang upload, hoàn thành hay thất bại.
            $table->string('status', 20)->default('pending')->index();

            // Bản local được giữ lại để retry và chỉ xóa sau khi xác minh file đã tồn tại trên Drive.
            $table->string('temporary_disk')->nullable();
            $table->string('temporary_path')->nullable();
            $table->text('web_view_link')->nullable();
            $table->text('web_content_link')->nullable();
            $table->text('error_message')->nullable();

            // Lưu người tải lên phục vụ kiểm toán và hiển thị trong File Manager.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_files');
    }
};
