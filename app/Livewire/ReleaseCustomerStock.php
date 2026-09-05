<?php

namespace App\Livewire;

use App\Models\CustomerStock;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReleaseCustomerStock extends Component
{
    #[Locked]
    public int $customerStockId;

    public function mount(int $customerStockId): void
    {
        $this->customerStockId = $customerStockId;
    }

    public function render(): View
    {
        return view('livewire.release-customer-stock', [
            'customerStock' => CustomerStock::query()
                ->with(['customer', 'productSku.product'])
                ->findOrFail($this->customerStockId),
        ]);
    }
}
