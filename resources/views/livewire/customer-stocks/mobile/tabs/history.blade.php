@forelse ($customerStock->releases as $release)
    <article wire:key="mobile-release-history-{{ $release->id }}" class="rounded-xl border border-slate-200 bg-white p-3.5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">{{ $release->release_code }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $release->released_at->format('d/m/Y H:i') }}</p>
            </div>
            <strong class="text-base text-blue-600 tabular-nums">{{ number_format((float) $release->total_amount, 0, ',', '.') }}đ</strong>
        </div>

        <div class="mt-3 space-y-2 border-y border-slate-100 py-3">
            @foreach ($release->items as $releaseItem)
                <div class="flex items-start justify-between gap-3 text-sm">
                    <div class="min-w-0">
                        <p class="break-words font-medium">{{ $releaseItem->customerStockItem->orderItem->productSku->product->name }}</p>
                        <p class="mt-0.5 break-all text-xs text-slate-500">{{ $releaseItem->customerStockItem->orderItem->productSku->sku_code }}</p>
                    </div>
                    <span class="shrink-0 tabular-nums">SL: {{ number_format($releaseItem->quantity, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <dl class="mt-3 space-y-1.5 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Tiền sản phẩm</dt><dd class="tabular-nums">{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Tiền dịch vụ</dt><dd class="text-blue-600 tabular-nums">{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Giảm giá</dt><dd class="text-red-600 tabular-nums">-{{ number_format((float) $release->allocated_discount, 0, ',', '.') }}đ</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Điều chỉnh</dt><dd class="tabular-nums">{{ (float) $release->reconciliation_adjustment > 0 ? '+' : '' }}{{ number_format((float) $release->reconciliation_adjustment, 0, ',', '.') }}đ</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phí giao hàng</dt><dd class="tabular-nums">{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}đ</dd></div>
            <div class="flex justify-between gap-3 border-t border-slate-100 pt-2"><dt class="text-slate-500">Người xuất</dt><dd class="text-right">{{ $release->creator?->name ?? 'Hệ thống' }}</dd></div>
        </dl>
    </article>
@empty
    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
        Chưa có phiếu xuất hàng.
    </p>
@endforelse
