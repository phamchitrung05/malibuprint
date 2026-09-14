<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Models\Service;
use App\Support\StatusApp;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueAnalytics
{
    /**
     * Payment là mốc ghi nhận: Order hoàn thành tháng trước nhưng thu tiền tháng này
     * được tính toàn bộ vào tháng này.
     *
     * @return array{
     *     labels: list<string>,
     *     revenue: list<float>,
     *     cup_printing_revenue: list<float>,
     *     cup_printing_quantity: list<int>
     * }
     */
    public function trailingMonths(int $months = 12): array
    {
        $months = max(1, min($months, 24));
        $periodEnd = CarbonImmutable::now(config('app.timezone'))->startOfMonth()->addMonth();
        $periodStart = $periodEnd->subMonths($months);
        $buckets = $this->emptyBuckets($periodStart, $months);
        $paymentMonth = $this->monthExpression('payment_date');

        $payments = DB::table('payment')
            ->where('status', StatusApp::value('payment.status', 'completed'))
            ->where('payment_date', '>=', $periodStart)
            ->where('payment_date', '<', $periodEnd)
            ->selectRaw("{$paymentMonth} AS month")
            ->selectRaw('COALESCE(SUM(amount), 0) AS revenue')
            ->groupByRaw($paymentMonth)
            ->get();

        foreach ($payments as $payment) {
            if (isset($buckets[$payment->month])) {
                $buckets[$payment->month]['revenue'] = (float) $payment->revenue;
            }
        }

        foreach ($this->cupPrintingRows($periodStart, $periodEnd)->get() as $service) {
            if (isset($buckets[$service->month])) {
                $buckets[$service->month]['cup_printing_revenue'] = (float) $service->subtotal;
                $buckets[$service->month]['cup_printing_quantity'] = (int) $service->quantity;
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'revenue' => array_column($buckets, 'revenue'),
            'cup_printing_revenue' => array_column($buckets, 'cup_printing_revenue'),
            'cup_printing_quantity' => array_column($buckets, 'cup_printing_quantity'),
        ];
    }

    /**
     * @return array{label: string, revenue: float, cup_printing_revenue: float, cup_printing_quantity: int}
     */
    public function currentMonth(): array
    {
        $series = $this->trailingMonths(1);

        return [
            'label' => $series['labels'][0],
            'revenue' => $series['revenue'][0],
            'cup_printing_revenue' => $series['cup_printing_revenue'][0],
            'cup_printing_quantity' => $series['cup_printing_quantity'][0],
        ];
    }

    /**
     * Đơn thường dùng snapshot dịch vụ trên Order Item; Customer Stock dùng snapshot
     * của đúng lần xuất đã thu tiền để không ghi nhận dịch vụ nhiều lần.
     */
    private function cupPrintingRows(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): Builder
    {
        $completedPayment = StatusApp::value('payment.status', 'completed');
        $paymentMonth = $this->monthExpression('paid_payment.payment_date');

        $singleOrderServices = DB::table('payment as paid_payment')
            ->join('orders', 'orders.id', '=', 'paid_payment.order_id')
            ->join('order_item', 'order_item.order_id', '=', 'orders.id')
            ->join('order_item_services', 'order_item_services.order_item_id', '=', 'order_item.id')
            ->join('services', 'services.id', '=', 'order_item_services.service_id')
            ->whereNull('paid_payment.stock_release_id')
            ->where('orders.fulfillment_mode', FulfillmentMode::Single->value)
            ->where('paid_payment.status', $completedPayment)
            ->where('paid_payment.payment_date', '>=', $periodStart)
            ->where('paid_payment.payment_date', '<', $periodEnd)
            ->where('services.code', Service::CUP_PRINTING_CODE)
            ->selectRaw("{$paymentMonth} AS month")
            ->selectRaw('SUM(order_item_services.quantity) AS quantity')
            ->selectRaw('SUM(order_item_services.subtotal) AS subtotal')
            ->groupByRaw($paymentMonth);

        $stockReleaseServices = DB::table('payment as paid_payment')
            ->join('stock_release_items', 'stock_release_items.stock_release_id', '=', 'paid_payment.stock_release_id')
            ->join('stock_release_item_services', 'stock_release_item_services.stock_release_item_id', '=', 'stock_release_items.id')
            ->join('order_item_services', 'order_item_services.id', '=', 'stock_release_item_services.order_item_service_id')
            ->join('services', 'services.id', '=', 'order_item_services.service_id')
            ->whereNotNull('paid_payment.stock_release_id')
            ->where('paid_payment.status', $completedPayment)
            ->where('paid_payment.payment_date', '>=', $periodStart)
            ->where('paid_payment.payment_date', '<', $periodEnd)
            ->where('services.code', Service::CUP_PRINTING_CODE)
            ->selectRaw("{$paymentMonth} AS month")
            ->selectRaw('SUM(stock_release_item_services.quantity) AS quantity')
            ->selectRaw('SUM(stock_release_item_services.subtotal) AS subtotal')
            ->groupByRaw($paymentMonth);

        return DB::query()
            ->fromSub($singleOrderServices->unionAll($stockReleaseServices), 'monthly_cup_printing')
            ->select('month')
            ->selectRaw('SUM(quantity) AS quantity')
            ->selectRaw('SUM(subtotal) AS subtotal')
            ->groupBy('month');
    }

    /** Database chỉ trả một dòng cho mỗi tháng; biểu thức được tách theo driver để giữ query có thể dùng index. */
    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlsrv' => "CONVERT(char(7), {$column}, 126)",
            default => "strftime('%Y-%m', {$column})",
        };
    }

    /**
     * Tạo đủ bucket kể cả tháng không có doanh thu để Chart.js luôn giữ trục thời gian liên tục.
     *
     * @return array<string, array{label: string, revenue: float, cup_printing_revenue: float, cup_printing_quantity: int}>
     */
    private function emptyBuckets(CarbonImmutable $periodStart, int $months): array
    {
        $buckets = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $periodStart->addMonths($offset);
            $buckets[$month->format('Y-m')] = [
                'label' => $month->format('m/Y'),
                'revenue' => 0.0,
                'cup_printing_revenue' => 0.0,
                'cup_printing_quantity' => 0,
            ];
        }

        return $buckets;
    }
}
