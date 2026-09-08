<?php

namespace App\Livewire\Orders;

use App\Jobs\UploadManagedFileToGoogleDrive;
use App\Models\Attachment;
use App\Models\ManagedFile;
use App\Support\StatusApp;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ManageAttachments extends Component
{
    // Hai thuộc tính bị khóa để request Livewire không thể đổi model đích sau khi component mount.
    #[Locked]
    public string $attachableType;

    #[Locked]
    public int $attachableId;

    public function mount(string $attachableType, int $attachableId): void
    {
        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
        $this->getAttachable();
    }

    public function detachFile(int $attachmentId): void
    {
        // Gỡ liên kết không xóa file vật lý vì file có thể đang được model khác sử dụng.
        $attachment = $this->getAttachable()->attachments()->findOrFail($attachmentId);
        $attachment->delete();

        Notification::make()->title('Đã gỡ liên kết file')->success()->send();
    }

    public function retryUpload(int $managedFileId): void
    {
        // Chỉ cho retry file thực sự thuộc model hiện tại, tránh thao tác chéo dữ liệu qua ID.
        $managedFile = ManagedFile::query()
            ->whereHas('attachments', fn ($query) => $query
                ->where('attachable_type', $this->attachableType)
                ->where('attachable_id', $this->attachableId))
            ->findOrFail($managedFileId);

        abort_unless(
            $managedFile->temporary_path
            && Storage::disk($managedFile->temporary_disk)->exists($managedFile->temporary_path),
            422,
            'File staging không còn tồn tại.',
        );

        $managedFile->update([
            'status' => StatusApp::default('managed_file.status'),
            'error_message' => null,
        ]);

        UploadManagedFileToGoogleDrive::dispatch($managedFile->id)
            ->onConnection(config('attachments.queue_connection'))
            ->onQueue(config('attachments.queue'));

        Notification::make()->title('Đã đưa file vào hàng chờ thử lại')->success()->send();
    }

    public function render(): View
    {
        $attachable = $this->getAttachable();
        $attachments = $attachable->attachments()->with(['managedFile.uploader'])->latest()->get();

        return view('livewire.orders.manage-attachments', [
            'attachments' => $attachments,
            'shouldPoll' => $attachments->contains(fn (Attachment $attachment): bool => in_array(
                $attachment->managedFile->status,
                [
                    StatusApp::value('managed_file.status', 'pending'),
                    StatusApp::value('managed_file.status', 'uploading'),
                ],
                true,
            )),
        ]);
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1024 / 1024, 1).' MB';
    }

    private function getAttachable(): Model
    {
        // Xác thực session, allowlist class và sự tồn tại của record ở mọi request Livewire.
        abort_unless(auth()->check(), 403);
        abort_unless(in_array($this->attachableType, config('attachments.allowed_attachable_models', []), true), 403);
        abort_unless(is_subclass_of($this->attachableType, Model::class), 422);

        return $this->attachableType::query()->findOrFail($this->attachableId);
    }
}
