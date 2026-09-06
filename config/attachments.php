<?php

use App\Models\Order;

return [
    // File được nhận nhanh vào private local storage trước khi queue chuyển sang Google Drive.
    'staging_disk' => env('ATTACHMENT_STAGING_DISK', 'attachment_staging'),
    'target_disk' => env('ATTACHMENT_TARGET_DISK', 'google'),

    // Queue riêng giúp upload file lớn không làm chậm các job mặc định của ứng dụng.
    'queue_connection' => env('ATTACHMENT_QUEUE_CONNECTION', 'database'),
    'queue' => env('ATTACHMENT_QUEUE', 'attachments'),

    // File mồ côi và file upload lỗi chỉ bị dọn sau thời gian giữ lại để còn cơ hội retry.
    'orphan_retention_hours' => (int) env('ATTACHMENT_ORPHAN_RETENTION_HOURS', 24),
    'failed_retention_days' => (int) env('ATTACHMENT_FAILED_RETENTION_DAYS', 7),

    // Allowlist ngăn client lợi dụng Livewire để gắn file vào model bất kỳ.
    'allowed_attachable_models' => [
        Order::class,
    ],
];
