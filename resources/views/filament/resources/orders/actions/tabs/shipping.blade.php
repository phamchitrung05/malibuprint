<section class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <x-heroicon-o-truck class="size-4"/>
            </div>
            <h3 class="text-sm font-bold text-slate-900">Thông tin giao hàng</h3>
        </div>
        <span @class([
            'rounded-md px-2 py-1 text-[11px] font-semibold',
            'bg-emerald-50 text-emerald-600' => $order->is_delivered,
            'bg-amber-50 text-amber-600' => ! $order->is_delivered,
        ])>{{ $order->is_delivered ? 'Đã giao' : 'Chưa giao' }}</span>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-2">
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Địa chỉ nhận hàng</p>
            <p class="mt-2 text-sm font-medium text-slate-800">{{ $order->customer?->address ?? 'Chưa có địa chỉ giao hàng' }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $order->customer?->phone ?? 'Chưa có số điện thoại' }}</p>
        </div>
        <div class="space-y-3">
            @forelse ($order->shipping as $shipping)
                <div class="rounded-lg border border-slate-200 p-4 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Trạng thái</span><b>{{ \App\Support\StatusApp::label('shipping.status', $shipping->status) }}</b></div>
                    <div class="mt-2 flex justify-between gap-3"><span class="text-slate-500">Ngày gửi</span><span>{{ $shipping->shipped_at?->format('d/m/Y H:i') ?? 'Chưa gửi' }}</span></div>
                    <div class="mt-2 flex justify-between gap-3"><span class="text-slate-500">Ngày giao</span><span>{{ $shipping->delivered_at?->format('d/m/Y H:i') ?? 'Chưa giao' }}</span></div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có phiếu giao hàng.</div>
            @endforelse
        </div>
    </div>
</section>
