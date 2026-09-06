<?php

namespace App\Console\Commands;

use App\Models\ManagedFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupAttachmentStaging extends Command
{
    protected $signature = 'attachments:cleanup-staging';

    protected $description = 'Xóa các file staging đã upload hoặc không còn được quản lý';

    public function handle(): int
    {
        $diskName = config('attachments.staging_disk');
        $disk = Storage::disk($diskName);
        $deleted = 0;

        // Dọn bản local của file đã lên Drive và file lỗi đã quá thời hạn cho phép retry.
        ManagedFile::query()
            ->whereNotNull('temporary_path')
            ->where(function ($query): void {
                $query->where('status', ManagedFile::STATUS_READY)
                    ->orWhere(function ($query): void {
                        $query->where('status', ManagedFile::STATUS_FAILED)
                            ->where('updated_at', '<=', now()->subDays(config('attachments.failed_retention_days')));
                    });
            })
            ->each(function (ManagedFile $file) use ($disk, &$deleted): void {
                if ($disk->exists($file->temporary_path) && $disk->delete($file->temporary_path)) {
                    $deleted++;
                }

                $file->update([
                    'temporary_disk' => null,
                    'temporary_path' => null,
                ]);
            });

        // Sau đó dọn file mồ côi không có ManagedFile, thường phát sinh khi form Order bị hủy hoặc rollback.
        $trackedPaths = ManagedFile::query()
            ->whereNotNull('temporary_path')
            ->pluck('temporary_path')
            ->flip();
        $orphanCutoff = now()->subHours(config('attachments.orphan_retention_hours'))->timestamp;

        foreach ($disk->allFiles() as $path) {
            if (! $trackedPaths->has($path) && $disk->lastModified($path) <= $orphanCutoff && $disk->delete($path)) {
                $deleted++;
            }
        }

        $this->info("Đã xóa {$deleted} file staging.");

        return self::SUCCESS;
    }
}
