<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Concerns\HandlesStagedAttachments;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\AttachmentManager;
use App\Services\OrderInventoryManager;
use App\Support\StatusApp;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use HandlesStagedAttachments;

    protected static string $resource = OrderResource::class;

    /** @var list<string> */
    private array $stagedAttachmentPaths = [];

    /** @var array<string, string> */
    private array $stagedAttachmentNames = [];

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        abort_if(in_array($this->record->status, [
            StatusApp::value('order.status', 'completed'),
            StatusApp::value('order.status', 'cancelled'),
        ], true), 403);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // File mới chỉ được liên kết sau khi Order và các thay đổi khác đã lưu thành công.
        $this->stagedAttachmentPaths = array_values(array_filter($data['new_attachments'] ?? []));
        $this->stagedAttachmentNames = $data['new_attachment_names'] ?? [];

        // Các trường hỗ trợ tạo mới không thuộc schema bảng orders nên không được đưa vào câu UPDATE.
        unset(
            $data['customer_mode'],
            $data['customer_name'],
            $data['customer_phone'],
            $data['customer_email'],
            $data['customer_company'],
            $data['customer_address'],
            $data['customer_note'],
            $data['new_attachments'],
            $data['new_attachment_names'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
        // Đồng bộ theo phần chênh lệch sau khi Repeater đã lưu Order Item mới.
        app(OrderInventoryManager::class)->syncForOrder($this->record->id, auth()->id());

        if ($this->stagedAttachmentPaths !== []) {
            app(AttachmentManager::class)->attachStagedPaths(
                $this->record,
                $this->stagedAttachmentPaths,
                $this->stagedAttachmentNames,
                userId: auth()->id(),
            );
        }
    }
}
