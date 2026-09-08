<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PrintDocumentFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrintOrders extends Controller
{
    public function __invoke(Request $request, PrintDocumentFactory $factory): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists(Order::class, 'id')],
        ]);
        $ids = collect($validated['ids'])->map(fn ($id): int => (int) $id)->values();
        $orderPositions = $ids->flip();
        $orders = Order::query()
            ->whereKey($ids)
            ->get()
            ->sortBy(fn (Order $order): int => $orderPositions[$order->id])
            ->values();

        return view('print.print-bulk', [
            'documents' => $orders->map(fn (Order $order): array => $factory->forOrder($order)),
        ]);
    }
}
