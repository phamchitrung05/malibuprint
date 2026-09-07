<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\OrderActivityLogger;

class PaymentObserver
{
    public function __construct(private readonly OrderActivityLogger $logger) {}

    public function created(Payment $payment): void
    {
        $this->logger->log($payment->order, 'payment.created', 'Đã ghi nhận thu tiền', [
            'payment_id' => $payment->id,
            'stock_release_id' => $payment->stock_release_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
        ]);
    }
}
