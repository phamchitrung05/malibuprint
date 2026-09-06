<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Concerns\HandlesStagedAttachments;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Services\AttachmentManager;
use App\Services\OrderCodeService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrder extends CreateRecord
{
    use HandlesStagedAttachments;

    protected static string $resource = OrderResource::class;

    /** @var list<string> */
    private array $stagedAttachmentPaths = [];

    /** @var array<string, string> */
    private array $stagedAttachmentNames = [];

    /**
     * Tạo Order trong cùng transaction với sequence để mã luôn duy nhất khi có hai người thao tác.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Tách file staging khỏi payload Order vì đây không phải cột của bảng orders.
        $this->stagedAttachmentPaths = array_values(array_filter($data['new_attachments'] ?? []));
        $this->stagedAttachmentNames = $data['new_attachment_names'] ?? [];
        unset($data['new_attachments'], $data['new_attachment_names']);

        return DB::transaction(function () use ($data): Order {
            if (($data['customer_mode'] ?? null) === 'new') {
                $customer = Customer::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'address' => $data['customer_address'] ?? null,
                    'note' => $data['customer_note'] ?? null,
                    'is_active' => true,
                ]);

                $data['customer_id'] = $customer->getKey();
            }

            unset(
                $data['customer_mode'],
                $data['customer_name'],
                $data['customer_phone'],
                $data['customer_email'],
                $data['customer_company'],
                $data['customer_address'],
                $data['customer_note'],
            );

            $data['order_date'] = now();
            $data['status'] = 'pending';

            $orderDate = Carbon::parse($data['order_date']);
            $data['order_code'] = app(OrderCodeService::class)->generate($orderDate);

            return Order::create($data);
        });
    }

    protected function afterCreate(): void
    {
        // Repeater lưu order_item sau Order, nên tổng tiền được chốt lại khi quan hệ đã lưu xong.
        $this->record->recalculateTotals();

        if ($this->stagedAttachmentPaths !== []) {
            // Filament đã lưu Order Item; lúc này mới tạo liên kết file và dispatch job sau commit.
            app(AttachmentManager::class)->attachStagedPaths(
                $this->record,
                $this->stagedAttachmentPaths,
                $this->stagedAttachmentNames,
                userId: auth()->id(),
            );
        }
    }
}
