<section class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><x-heroicon-o-pencil-square class="size-4"/></div>
            <h3 class="text-sm font-bold text-slate-900">Ghi chú đơn hàng</h3>
        </div>
        <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700">Chỉnh sửa</button>
    </div>
    <div class="whitespace-pre-line rounded-lg bg-amber-50 p-4 text-sm leading-7 text-slate-700">{{ $order->note ?: 'Chưa có ghi chú cho đơn hàng.' }}</div>
</section>
