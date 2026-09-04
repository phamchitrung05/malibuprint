<?php

namespace App\Filament\Resources\CustomerStocks\Pages;

use App\Filament\Resources\CustomerStocks\CustomerStockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerStock extends CreateRecord
{
    protected static string $resource = CustomerStockResource::class;
}
