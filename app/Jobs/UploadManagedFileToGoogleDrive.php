<?php

namespace App\Jobs;

use App\Models\ManagedFile;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadManagedFileToGoogleDrive implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    // Retry theo nhịp tăng dần để chịu được lỗi mạng hoặc Google Drive gián đoạn tạm thời.
    public int $tries = 3;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $managedFileId) {}

    public function uniqueId(): string
    {
        // Mỗi ManagedFile chỉ có một job upload hoạt động tại cùng thời điểm.
        return (string) $this->managedFileId;
    }

    public function handle(): void
    {
        $managedFile = ManagedFile::query()->findOrFail($this->managedFileId);

        if ($managedFile->status === ManagedFile::STATUS_READY) {
            // Job có thể được chạy lại an toàn mà không tạo bản sao trên Drive.
            return;
        }

        $temporaryDisk = Storage::disk($managedFile->temporary_disk);
        $targetDisk = Storage::disk($managedFile->disk);

        if (! $managedFile->temporary_path || ! $temporaryDisk->exists($managedFile->temporary_path)) {
            throw new RuntimeException('File staging không còn tồn tại.');
        }

        $managedFile->update([
            'status' => ManagedFile::STATUS_UPLOADING,
            'error_message' => null,
        ]);

        if (! $targetDisk->exists($managedFile->path)) {
            // Truyền stream giúp không nạp toàn bộ file lớn vào bộ nhớ PHP.
            $stream = $temporaryDisk->readStream($managedFile->temporary_path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Không thể đọc file staging.');
            }

            try {
                $uploaded = $targetDisk->writeStream($managedFile->path, $stream);
            } finally {
                // Google adapter có thể tự đóng stream; chỉ đóng nếu resource vẫn còn hợp lệ.
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $uploaded) {
                throw new RuntimeException('Google Drive từ chối upload file.');
            }
        }

        if (! $targetDisk->exists($managedFile->path)) {
            throw new RuntimeException('Không tìm thấy file sau khi upload lên Google Drive.');
        }

        $contentUrl = $targetDisk->url($managedFile->path);
        $driveFileId = $this->extractDriveFileId($contentUrl);

        // Đánh dấu ready trước, sau đó mới xóa staging để không mất bản duy nhất khi cập nhật DB lỗi.
        $managedFile->update([
            'drive_file_id' => $driveFileId,
            'web_view_link' => $driveFileId ? "https://drive.google.com/file/d/{$driveFileId}/view" : $contentUrl,
            'web_content_link' => $contentUrl,
            'status' => ManagedFile::STATUS_READY,
            'uploaded_at' => now(),
            'error_message' => null,
        ]);

        if ($temporaryDisk->delete($managedFile->temporary_path)) {
            // Xóa đường dẫn tạm khỏi DB để cleanup command không xử lý lại file đã hoàn tất.
            $managedFile->update([
                'temporary_disk' => null,
                'temporary_path' => null,
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        // File staging được giữ nguyên khi thất bại để admin có thể bấm thử lại.
        ManagedFile::query()->whereKey($this->managedFileId)->update([
            'status' => ManagedFile::STATUS_FAILED,
            'error_message' => $exception?->getMessage(),
        ]);
    }

    private function extractDriveFileId(string $url): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (filled($query['id'] ?? null)) {
            return (string) $query['id'];
        }

        return preg_match('~/d/([^/]+)~', $url, $matches) ? $matches[1] : null;
    }
}
