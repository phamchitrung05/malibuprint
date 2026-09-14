<?php

namespace App\Filament\Widgets;

use App\Services\MonthlyRevenueAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class MonthlyRevenueStats extends StatsOverviewWidget
{
    protected static ?int $sort = -4;

    protected ?string $pollingInterval = '30s';

    protected int|array|null $columns = 2;

    protected function getHeading(): ?string
    {
        return 'Tổng quan doanh thu tháng này';
    }

    protected function getStats(): array
    {
        $metrics = app(MonthlyRevenueAnalytics::class)->currentMonth();

        return [
            Stat::make(
                "Doanh thu tháng {$metrics['label']}",
                Number::currency($metrics['revenue'], in: 'VND', locale: 'vi', precision: 0),
            )
                // Chỉ Payment completed mới là khoản tiền đã thực nhận.
                ->description('Tổng các phiếu thu đã hoàn tất')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make(
                "Số lượng dịch vụ in ly tháng {$metrics['label']}",
                Number::format($metrics['cup_printing_quantity'], locale: 'vi'),
            )
                // Customer Stock chỉ ghi nhận quantity của đợt xuất đã được thanh toán.
                ->description('Số sản phẩm in ly đã thu tiền')
                ->descriptionIcon('heroicon-m-printer')
                ->color('info'),
        ];
    }
}
