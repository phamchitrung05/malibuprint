<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PrintDocumentFactory;
use Illuminate\Contracts\View\View;

class PrintOrder extends Controller
{
    public function __invoke(Order $order, PrintDocumentFactory $factory): View
    {
        return view('print.print', [
            'documents' => collect([$factory->forOrder($order)]),
        ]);
    }
}
