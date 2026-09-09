@forelse ($customerStock->items as $item)
    @php
        $sku = $item->orderItem->productSku;
        $remainingQuantity = $item->remainingQuantity();
        $selectedQuantity = max(0, (int) ($quantities[$item->id] ?? 0));
        $serviceUnitPrice = $item->orderItem->services->sum(
            fn ($service): float => (float) $service->unit_price,
        );
    @endphp
    <article wire:key="mobile-release-stock-item-{{ $item->id }}" class="rounded-xl border border-blue-200 bg-white p-3.5">
        <h3 class="break-words text-lg font-semibold leading-6">{{ $sku->product->name }}</h3>
        <p class="mt-1 break-all text-xs text-slate-500">
            SKU: {{ $sku->sku_code }} <span class="mx-1">·</span> ĐVT: {{ $sku->product->unit }}
        </p>

        <dl class="my-3 grid grid-cols-3 divide-x divide-blue-100 text-center">
            <div>
                <dt class="text-xs text-slate-500">Nhập kho</dt>
                <dd class="mt-1 text-base font-medium tabular-nums">{{ number_format($item->received_quantity, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Đã xuất</dt>
                <dd class="mt-1 text-base font-medium tabular-nums">{{ number_format($item->released_quantity, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Còn lại</dt>
                <dd class="mt-1 text-base font-semibold tabular-nums">{{ number_format($remainingQuantity, 0, ',', '.') }}</dd>
            </div>
        </dl>

        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 rounded-lg bg-blue-50/80 p-2.5">
            <div class="min-w-0 space-y-1">
                @forelse ($item->orderItem->services as $service)
                    <div>
                        <p class="break-words font-medium text-blue-600">{{ $service->service_name }}</p>
                        <p class="text-xs text-slate-500">{{ number_format((float) $service->unit_price, 0, ',', '.') }}đ/SP</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Dịch vụ: Không có</p>
                @endforelse
            </div>
            <div class="border-l border-blue-200 pl-3 text-right">
                <p class="text-xs text-slate-500">Phí DV còn lại</p>
                <p class="font-medium text-blue-600 tabular-nums">{{ number_format($remainingQuantity * $serviceUnitPrice, 0, ',', '.') }}đ</p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <label for="mobile-release-quantity-{{ $item->id }}" class="text-sm text-slate-500">Xuất lần này</label>
            <div @class([
                'flex h-10 overflow-hidden rounded-lg border bg-slate-50',
                'border-slate-100 text-slate-400' => $remainingQuantity === 0,
                'border-slate-200 text-slate-700' => $remainingQuantity > 0,
            ])>
                <button
                    type="button"
                    wire:click="$set('quantities.{{ $item->id }}', {{ max(0, $selectedQuantity - 1) }})"
                    @disabled($remainingQuantity === 0 || $selectedQuantity === 0)
                    aria-label="Giảm số lượng {{ $sku->product->name }}"
                    class="flex w-10 items-center justify-center bg-slate-100 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-heroicon-o-minus class="size-4"/>
                </button>
                <input
                    id="mobile-release-quantity-{{ $item->id }}"
                    type="number"
                    inputmode="numeric"
                    min="0"
                    max="{{ $remainingQuantity }}"
                    step="1"
                    wire:model.live.debounce.300ms="quantities.{{ $item->id }}"
                    @disabled($remainingQuantity === 0)
                    class="release-quantity-input w-14 border-0 bg-transparent p-0 text-center text-sm tabular-nums outline-none disabled:cursor-not-allowed"
                >
                <button
                    type="button"
                    wire:click="$set('quantities.{{ $item->id }}', {{ min($remainingQuantity, $selectedQuantity + 1) }})"
                    @disabled($remainingQuantity === 0 || $selectedQuantity >= $remainingQuantity)
                    aria-label="Tăng số lượng {{ $sku->product->name }}"
                    class="flex w-10 items-center justify-center bg-slate-100 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-heroicon-o-plus class="size-4"/>
                </button>
            </div>
        </div>
        @error("quantities.{$item->id}")
            <p class="pt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </article>
@empty
    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
        Order này chưa có hàng tồn.
    </p>
@endforelse

@error('quantities')
    <p class="text-sm text-red-600">{{ $message }}</p>
@enderror

<dl class="space-y-2 rounded-xl border border-blue-200 bg-blue-50/60 p-3.5 text-sm">
    <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Tiền sản phẩm dự kiến</dt>
        <dd class="shrink-0 font-medium tabular-nums">{{ number_format($releaseProductPreview, 0, ',', '.') }}đ</dd>
    </div>
    <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Tiền dịch vụ dự kiến</dt>
        <dd class="shrink-0 font-medium text-blue-600 tabular-nums">{{ number_format($releaseServicePreview, 0, ',', '.') }}đ</dd>
    </div>
    <div class="flex justify-between gap-3 border-t border-blue-200 pt-2.5 text-base font-semibold">
        <dt>Tạm tính trước phân bổ</dt>
        <dd class="shrink-0 tabular-nums">{{ number_format($releaseProductPreview + $releaseServicePreview, 0, ',', '.') }}đ</dd>
    </div>
</dl>
