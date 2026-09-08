<?php

use App\Http\Controllers\DownloadManagedFile;
use App\Http\Controllers\PrintOrder;
use App\Http\Controllers\PrintOrders;
use App\Http\Controllers\PrintStockReleaseReceipt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// File staging là dữ liệu private, chỉ người dùng đã đăng nhập mới được tải trước khi lên Drive.
Route::get('/managed-files/{managedFile:uuid}/download', DownloadManagedFile::class)
    ->middleware('auth')
    ->name('managed-files.download');

Route::get('/orders/print/bulk', PrintOrders::class)
    ->middleware('auth')
    ->name('orders.print.bulk');

Route::get('/orders/{order}/print', PrintOrder::class)
    ->middleware('auth')
    ->name('orders.print');

// Phiếu thu chứa dữ liệu khách hàng và tài chính nên chỉ người dùng đã đăng nhập mới được in.
Route::get('/stock-releases/{stockRelease}/receipt/print', PrintStockReleaseReceipt::class)
    ->middleware('auth')
    ->name('stock-releases.receipt.print');
