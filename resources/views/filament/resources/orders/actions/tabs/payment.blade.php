<div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_2fr]">
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-heroicon-o-credit-card class="size-4"/>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Tình trạng thanh toán</h3>
            </div>
            <span @class([
                'rounded-md px-2 py-1 text-[11px] font-semibold',
                'bg-emerald-50 text-emerald-600' => $order->is_paid,
                'bg-red-50 text-red-600' => ! $order->is_paid,
            ])>{{ $order->is_paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}</span>
        </div>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Tổng đơn hàng</dt>
                <dd class="font-semibold text-slate-800">{{ number_format((float) $order->total_amount, 0, ',', '.') }}đ</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Đã ghi nhận</dt>
                <dd class="font-semibold text-slate-800">{{ number_format((float) $order->payments->sum('amount'), 0, ',', '.') }}đ</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Lịch sử thanh toán</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
                <tr><th class="px-5 py-3">Ngày thanh toán</th><th class="px-3 py-3">Trạng thái</th><th class="px-3 py-3">Ghi chú</th><th class="px-5 py-3 text-right">Số tiền</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($order->payments as $payment)
                    <tr>
                        <td class="px-5 py-4">{{ $payment->payment_date?->format('d/m/Y H:i') ?? '--/--' }}</td>
                        <td class="px-3 py-4 font-medium text-emerald-600">{{ $payment->status === 'completed' ? 'Hoàn thành' : $payment->status }}</td>
                        <td class="px-3 py-4 text-slate-500">{{ $payment->note ?: 'Không có ghi chú' }}</td>
                        <td class="px-5 py-4 text-right font-semibold">{{ number_format((float) $payment->amount, 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Chưa có giao dịch thanh toán.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
