<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;


    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        abort_if(in_array($this->record->status, ['completed', 'cancelled'], true), 403);
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

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
