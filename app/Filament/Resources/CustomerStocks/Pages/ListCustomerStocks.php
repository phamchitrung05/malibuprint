<?php

namespace App\Filament\Resources\CustomerStocks\Pages;

use App\Filament\Resources\CustomerStocks\CustomerStockResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListCustomerStocks extends ListRecords
{
    protected static string $resource = CustomerStockResource::class;

    #[On('customer-stock-updated')]
    public function refreshCustomerStocks(): void
    {
        // Event từ modal làm Livewire render lại table; không cần truy vấn thủ công tại component con.
    }
}
