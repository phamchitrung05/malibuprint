<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chạy ngoài giờ làm việc để dọn staging mà không ảnh hưởng thao tác upload của admin.
Schedule::command('attachments:cleanup-staging')->dailyAt('02:00');
