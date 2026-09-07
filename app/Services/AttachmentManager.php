<?php

namespace App\Services;

use App\Jobs\UploadManagedFileToGoogleDrive;
use App\Models\Attachment;
use App\Models\ManagedFile;
use App\Support\StatusApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AttachmentManager
{
    /**
     * Tạo metadata và liên kết từ các file đã được Filament lưu vào staging disk.
     *
     * @param  list<string>  $paths
     * @param  array<string, string>  $originalNames
     * @return list<ManagedFile>
     */
    public function attachStagedPaths(
        Model $attachable,
        array $paths,
        array $originalNames = [],
        string $collection = 'attachments',
        ?int $userId = null,
    ): array {
        // Chỉ chấp nhận model trong allowlist để không thể giả mạo attachableType từ Livewire.
        $this->ensureAttachableIsAllowed($attachable);

        return DB::transaction(function () use ($attachable, $paths, $originalNames, $collection, $userId): array {
            $files = [];
            $stagingDiskName = config('attachments.staging_disk');
            $targetDiskName = config('attachments.target_disk');
            $stagingDisk = Storage::disk($stagingDiskName);

            foreach (array_unique($paths) as $path) {
                // Không tạo metadata nếu Filament chưa lưu file local thành công.
                if (! $stagingDisk->exists($path)) {
                    throw new InvalidArgumentException("Không tìm thấy file staging: {$path}");
                }

                $originalName = basename(str_replace('\\', '/', $originalNames[$path] ?? basename($path)));
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
                $uuid = (string) Str::uuid();
                $storageName = $uuid.'-'.$baseName.($extension !== '' ? '.'.$extension : '');

                // UUID trong tên file ngăn hai file trùng tên ghi đè nhau trên Google Drive.
                $managedFile = ManagedFile::query()->create([
                    'uuid' => $uuid,
                    'disk' => $targetDiskName,
                    'original_name' => $originalName,
                    'storage_name' => $storageName,
                    'path' => now()->format('Y/m').'/'.$storageName,
                    'mime_type' => $stagingDisk->mimeType($path),
                    'extension' => $extension ?: null,
                    'size' => $stagingDisk->size($path),
                    'checksum' => $stagingDisk->checksum($path),
                    'status' => StatusApp::default('managed_file.status'),
                    'temporary_disk' => $stagingDiskName,
                    'temporary_path' => $path,
                    // URL có xác thực cho phép tải bản staging trong lúc worker chưa upload xong.
                    'web_view_link' => route('managed-files.download', ['managedFile' => $uuid], absolute: false),
                    'uploaded_by' => $userId,
                ]);

                $attachment = new Attachment([
                    'managed_file_id' => $managedFile->id,
                    'collection' => $collection,
                    'attached_by' => $userId,
                ]);
                $attachable->attachments()->save($attachment);

                // afterCommit bảo đảm worker không chạy trước khi Order, ManagedFile và Attachment được commit.
                UploadManagedFileToGoogleDrive::dispatch($managedFile->id)
                    ->onConnection(config('attachments.queue_connection'))
                    ->onQueue(config('attachments.queue'))
                    ->afterCommit();

                $files[] = $managedFile;
            }

            return $files;
        });
    }

    public function attachExisting(Model $attachable, ManagedFile $managedFile, string $collection = 'attachments', ?int $userId = null): Attachment
    {
        $this->ensureAttachableIsAllowed($attachable);

        if ($managedFile->status !== StatusApp::value('managed_file.status', 'ready')) {
            throw new InvalidArgumentException('Chỉ có thể liên kết file đã upload thành công.');
        }

        // firstOrCreate kết hợp unique index giúp thao tác chọn lại file luôn idempotent.
        return $attachable->attachments()->firstOrCreate([
            'managed_file_id' => $managedFile->id,
            'collection' => $collection,
        ], [
            'attached_by' => $userId,
        ]);
    }

    private function ensureAttachableIsAllowed(Model $attachable): void
    {
        if (! in_array($attachable::class, config('attachments.allowed_attachable_models', []), true)) {
            throw new InvalidArgumentException('Model này chưa được phép sử dụng File Manager.');
        }
    }
}
