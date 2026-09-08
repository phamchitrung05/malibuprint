<?php

namespace App\Livewire\CustomerStocks;

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
                'items.orderItem.services',
                'releases.creator',
                'releases.items.customerStockItem.orderItem.productSku.product',
                'releases.items.services',
            ])
            ->findOrFail($this->customerStockId);

        // Hai chỉ số tổng quan chỉ tính phần hàng còn nằm trong kho của khách, không gồm hàng đã xuất.
        $totalRemainingQuantity = $customerStock->items->sum(
            fn ($item): int => $item->remainingQuantity(),
        );
        $remainingStockValue = $customerStock->items->sum(
            fn ($item): float => $item->remainingQuantity() * (float) $item->orderItem->unit_price,
        );
        // Tổng dịch vụ của Order dùng subtotal snapshot, không phụ thuộc giá catalog hiện tại.
        $totalServiceValue = $customerStock->items->sum(
            fn ($item): float => $item->orderItem->services->sum(fn ($service): float => (float) $service->subtotal),
        );
        $releaseProductPreview = $customerStock->items->sum(function ($item): float {
            $quantity = min($item->remainingQuantity(), max(0, (int) ($this->quantities[$item->id] ?? 0)));

            return $quantity * (float) $item->orderItem->unit_price;
        });
        $releaseServicePreview = $customerStock->items->sum(function ($item): float {
            $quantity = min($item->remainingQuantity(), max(0, (int) ($this->quantities[$item->id] ?? 0)));

            return $quantity
                * $item->orderItem->services->sum(fn ($service): float => (float) $service->unit_price);
        });

        return view('livewire.customer-stocks.release-customer-stock', [
            'customerStock' => $customerStock,
            'totalRemainingQuantity' => $totalRemainingQuantity,
            'remainingStockValue' => $remainingStockValue,
            'totalServiceValue' => $totalServiceValue,
            'releaseProductPreview' => $releaseProductPreview,
            'releaseServicePreview' => $releaseServicePreview,
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
