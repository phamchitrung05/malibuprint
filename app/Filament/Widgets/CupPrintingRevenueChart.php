<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ConfiguresMonthlyRevenueChart;
use App\Services\MonthlyRevenueAnalytics;
use Filament\Widgets\ChartWidget;

class CupPrintingRevenueChart extends ChartWidget
{
    use ConfiguresMonthlyRevenueChart;

    protected static ?int $sort = 0;

    protected ?string $heading = 'Doanh thu dịch vụ in ly theo tháng';

    protected ?string $description = 'Dùng giá dịch vụ snapshot của đơn hoặc đợt xuất đã thu tiền.';

    protected string $color = 'info';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $series = app(MonthlyRevenueAnalytics::class)->trailingMonths($this->selectedMonths());

        return [
            'datasets' => [[
                'label' => 'Doanh thu dịch vụ in ly',
                'data' => $series['cup_printing_revenue'],
            ]],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
