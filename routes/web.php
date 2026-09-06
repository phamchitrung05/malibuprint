<?php

use App\Http\Controllers\DownloadManagedFile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// File staging là dữ liệu private, chỉ người dùng đã đăng nhập mới được tải trước khi lên Drive.
Route::get('/managed-files/{managedFile:uuid}/download', DownloadManagedFile::class)
    ->middleware('auth')
    ->name('managed-files.download');
