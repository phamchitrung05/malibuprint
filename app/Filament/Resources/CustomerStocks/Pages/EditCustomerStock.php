<?php

namespace App\Filament\Resources\CustomerStocks\Pages;

use App\Filament\Resources\CustomerStocks\CustomerStockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerStock extends EditRecord
{
    protected static string $resource = CustomerStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
