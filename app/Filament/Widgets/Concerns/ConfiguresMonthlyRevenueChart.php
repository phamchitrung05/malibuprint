<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

trait ConfiguresMonthlyRevenueChart
{
    use HasFiltersSchema;

    /** Dùng Select của Filament thay cho select native để dropdown đồng nhất với giao diện admin. */
    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('months')
                ->label('Khoảng thời gian')
                ->options([
                    '6' => '6 tháng gần nhất',
                    '12' => '12 tháng gần nhất',
                    '24' => '24 tháng gần nhất',
                ])
                ->default('6')
                ->native(false)
                ->selectablePlaceholder(false),
        ]);
    }

    /** Giá trị filter đến từ trình duyệt nên chỉ ánh xạ qua danh sách được phép. */
    protected function selectedMonths(): int
    {
        return match ($this->filters['months'] ?? '6') {
            '12' => 12,
            '24' => 24,
            default => 6,
        };
    }

    /** Định dạng trục và tooltip tiền tệ thống nhất cho các biểu đồ doanh thu. */
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0,
                            }).format(context.parsed.y ?? 0),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => new Intl.NumberFormat('vi-VN', {
                                notation: 'compact',
                                maximumFractionDigits: 1,
                            }).format(value),
                        },
                    },
                },
            }
            JS);
    }
}
