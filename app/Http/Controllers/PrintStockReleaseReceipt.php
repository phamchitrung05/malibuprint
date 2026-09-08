<?php

namespace App\Http\Controllers;

use App\Models\StockRelease;
use App\Services\PrintDocumentFactory;
use Illuminate\Contracts\View\View;

class PrintStockReleaseReceipt extends Controller
{
    public function __invoke(StockRelease $stockRelease, PrintDocumentFactory $factory): View
    {
        // Phiếu thu chỉ tồn tại sau khi admin xác nhận Payment; không cho in chứng từ chưa thu tiền.
        $stockRelease->load([
            'customerStock.customer',
            'customerStock.order',
            'items.customerStockItem.orderItem.productSku.product',
            'items.services',
            'payment.confirmer',
        ]);

        abort_if($stockRelease->payment === null, 404);

        return view('print.stock-release-receipt', [
            'documents' => collect([$factory->forStockRelease($stockRelease)]),
        ]);
    }
}
