<?php

namespace App\Livewire;

use App\Models\CustomerStock;
use App\Services\PaymentManager;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CustomerStockReleaseHistory extends Component
{
    #[Locked]
    public int $customerStockId;

    public ?string $paymentNote = null;

    public function mount(int $customerStockId): void
    {
        $this->customerStockId = $customerStockId;
    }

    public function confirmPayment(int $stockReleaseId): void
    {
        $this->validate([
            'paymentNote' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(auth()->check(), 403);

        // Service luôn scope phiếu xuất qua Customer Stock hiện tại để client không thể xác nhận phiếu của Order khác.
        app(PaymentManager::class)->confirmStockRelease(
            $this->customerStockId,
            $stockReleaseId,
            $this->paymentNote,
            auth()->id(),
        );

        $this->paymentNote = null;
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
                    'customer',
                    'order',
                    'releases.creator',
                    'releases.payment',
                    'releases.shipping',
                    'releases.items.customerStockItem.orderItem.productSku.product',
                ])
                ->findOrFail($this->customerStockId),
        ]);
    }
}
