<?php

namespace App\Livewire;

use App\Models\CustomerStock;
use App\Services\PaymentManager;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerStockReleaseHistory extends Component
{
    #[Locked]
    public int $customerStockId;

    public function mount(int $customerStockId): void
    {
        $this->customerStockId = $customerStockId;
    }

    #[On('customer-stock-updated')]
    public function refreshHistory(): void
    {
        // Phiếu xuất mới hoặc Payment mới sẽ được lấy lại trong lần render kế tiếp.
    }

    public function confirmPayment(int $stockReleaseId): void
    {
        abort_unless(auth()->check(), 403);

        // Service luôn scope phiếu xuất qua Customer Stock hiện tại để client không thể xác nhận phiếu của Order khác.
        app(PaymentManager::class)->confirmStockRelease(
            $this->customerStockId,
            $stockReleaseId,
            null,
            auth()->id(),
        );

        $this->dispatch('customer-stock-updated');

        Notification::make()
            ->title('Đã xác nhận thu tiền phiếu xuất')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.customer-stock-release-history', [
            'customerStock' => CustomerStock::query()
                ->with([
                    'releases.payment.confirmer',
                    'releases.items.customerStockItem.orderItem.productSku',
                ])
                ->findOrFail($this->customerStockId),
        ]);
    }
}
