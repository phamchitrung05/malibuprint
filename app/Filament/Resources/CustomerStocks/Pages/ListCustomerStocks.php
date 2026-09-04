<?php

namespace App\Filament\Resources\CustomerStocks\Pages;

use App\Filament\Resources\CustomerStocks\CustomerStockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerStocks extends ListRecords
{
    protected static string $resource = CustomerStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
