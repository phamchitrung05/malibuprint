<?php

namespace App\Http\Controllers;

use App\Models\StockRelease;
use App\Services\PrintDocumentFactory;
use Illuminate\Contracts\View\View;

class PrintStockReleaseReceipt extends Controller
{
    public function __invoke(StockRelease $stockRelease, PrintDocumentFactory $factory): View
    {
        // Payment có thể chưa tồn tại khi người dùng in trước; factory sẽ dùng dữ liệu phiếu xuất làm fallback.
        $stockRelease->load([
            'customerStock.customer',
            'customerStock.order',
            'items.customerStockItem.orderItem.productSku.product',
            'items.services',
            'payment.confirmer',
        ]);

        return view('print.stock-release-receipt', [
            'documents' => collect([$factory->forStockRelease($stockRelease)]),
        ]);
    }
}
