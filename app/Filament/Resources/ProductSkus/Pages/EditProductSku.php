<?php

namespace App\Filament\Resources\ProductSkus\Pages;

use App\Filament\Resources\ProductSkus\ProductSkuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductSku extends EditRecord
{
    protected static string $resource = ProductSkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
