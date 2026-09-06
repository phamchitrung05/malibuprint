<?php

namespace App\Filament\Resources\Orders\Concerns;

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HandlesStagedAttachments
{
    public function removeStagedAttachment(string $key): void
    {
        $files = $this->data['new_attachments'] ?? [];
        abort_unless(is_array($files) && array_key_exists($key, $files), 404);

        $file = $files[$key];

        if ($file instanceof TemporaryUploadedFile) {
            $file->delete();
        } elseif (is_string($file)) {
            // File đã chuyển vào staging nhưng chưa liên kết phải được xóa khi người dùng bấm Gỡ.
            Storage::disk(config('attachments.staging_disk'))->delete($file);
            unset($this->data['new_attachment_names'][$file]);
        }

        unset($files[$key]);
        $this->data['new_attachments'] = $files;
    }
}
