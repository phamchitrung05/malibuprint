<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ConfiguresMonthlyRevenueChart;
use App\Services\MonthlyRevenueAnalytics;
use Filament\Widgets\ChartWidget;

class TotalRevenueChart extends ChartWidget
{
    use ConfiguresMonthlyRevenueChart;

    protected static ?int $sort = -1;

    protected ?string $heading = 'Tổng doanh thu theo tháng';

    protected ?string $description = 'Tính theo ngày xác nhận phiếu thu hoàn tất.';

    protected string $color = 'success';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $series = app(MonthlyRevenueAnalytics::class)->trailingMonths($this->selectedMonths());

        return [
            'datasets' => [[
                'label' => 'Doanh thu',
                'data' => $series['revenue'],
            ]],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
