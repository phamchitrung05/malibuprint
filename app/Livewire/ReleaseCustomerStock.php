<?php

namespace App\Livewire;

use App\Models\CustomerStock;
use App\Services\StockReleaseManager;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReleaseCustomerStock extends Component
{
    #[Locked]
    public int $customerStockId;

    /** @var array<int, int|string|null> */
    public array $quantities = [];

    public function mount(int $customerStockId): void
    {
        $this->customerStockId = $customerStockId;
        $this->resetQuantities();
    }

    public function release(): void
    {
        $this->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
        ]);

        abort_unless(auth()->check(), 403);

        app(StockReleaseManager::class)->release(
            $this->customerStockId,
            $this->quantities,
            null,
            auth()->id(),
        );

        $this->resetQuantities();
        $this->dispatch('customer-stock-updated');

        Notification::make()
            ->title('Đã tạo phiếu xuất và xác nhận giao hàng')
            ->success()
            ->send();
    }

    public function render(): View
    {
        $customerStock = CustomerStock::query()
            ->with([
                'customer',
                'order',
                'items.orderItem.productSku.product',
                'releases.creator',
                'releases.items',
            ])
            ->findOrFail($this->customerStockId);

        // Hai chỉ số tổng quan chỉ tính phần hàng còn nằm trong kho của khách, không gồm hàng đã xuất.
        $totalRemainingQuantity = $customerStock->items->sum(
            fn ($item): int => $item->remainingQuantity(),
        );
        $remainingStockValue = $customerStock->items->sum(
            fn ($item): float => $item->remainingQuantity() * (float) $item->orderItem->unit_price,
        );

        return view('livewire.release-customer-stock', [
            'customerStock' => $customerStock,
            'totalRemainingQuantity' => $totalRemainingQuantity,
            'remainingStockValue' => $remainingStockValue,
        ]);
    }

    private function resetQuantities(): void
    {
        $this->quantities = CustomerStock::query()
            ->findOrFail($this->customerStockId)
            ->items()
            ->pluck('id')
            ->mapWithKeys(fn (int $id): array => [$id => 0])
            ->all();
    }
}
