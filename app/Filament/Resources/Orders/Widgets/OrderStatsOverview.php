<?php

namespace App\Filament\Resources\Orders\Widgets;

use App\Models\Order;
use App\Support\StatusApp;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $counts = Order::query()
            ->toBase()
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS pending_count', [
                StatusApp::value('order.status', 'pending'),
            ])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS processing_count', [
                StatusApp::value('order.status', 'processing'),
            ])
            ->selectRaw('COUNT(CASE WHEN status = ? AND is_delivered = ? THEN 1 END) AS awaiting_delivery_count', [
                StatusApp::value('order.status', 'completed'),
                false,
            ])
            ->selectRaw('COUNT(CASE WHEN status = ? AND is_paid = ? THEN 1 END) AS awaiting_payment_count', [
                StatusApp::value('order.status', 'completed'),
                false,
            ])
            ->first();

        return [
            Stat::make('Đơn mới tạo', (int) $counts->pending_count)
                ->description('Đơn đang chờ tiếp nhận')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
            Stat::make('Đơn đang xử lý', (int) $counts->processing_count)
                ->description('Đơn đang trong sản xuất')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Hoàn thành chưa giao', (int) $counts->awaiting_delivery_count)
                ->description('Đơn đang chờ giao hàng')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
            Stat::make('Hoàn thành chưa thanh toán', (int) $counts->awaiting_payment_count)
                ->description('Đơn đang chờ thanh toán')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('danger'),
        ];
    }
}
