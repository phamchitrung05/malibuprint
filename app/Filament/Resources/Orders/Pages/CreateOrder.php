<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderCodeService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Tạo Order trong cùng transaction với sequence để mã luôn duy nhất khi có hai người thao tác.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Order {
            $orderDate = Carbon::parse($data['order_date'] ?? now());
            $data['order_code'] = app(OrderCodeService::class)->generate($orderDate);

            return Order::create($data);
        });
    }
}
