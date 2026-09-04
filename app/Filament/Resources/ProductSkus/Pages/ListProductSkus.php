<?php

namespace App\Filament\Resources\ProductSkus\Pages;

use App\Filament\Resources\ProductSkus\ProductSkuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductSkus extends ListRecords
{
    protected static string $resource = ProductSkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
