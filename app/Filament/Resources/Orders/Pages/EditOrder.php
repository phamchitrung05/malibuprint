<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['customer_mode'],
            $data['customer_name'],
            $data['customer_phone'],
            $data['customer_email'],
            $data['customer_company'],
            $data['customer_address'],
            $data['customer_note'],
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
