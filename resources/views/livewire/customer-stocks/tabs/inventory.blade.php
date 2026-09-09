<div class="overflow-hidden rounded-xl border border-slate-200">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1100px] border-collapse">
            <thead>
                <tr class="bg-slate-100/90 text-left">
                    <th class="text-left font-semibold text-gray-700 dark:text-gray-200">Sản phẩm</th>
                    <th class="text-left font-semibold text-gray-700 dark:text-gray-200">SKU</th>
                    <th class="text-left font-semibold text-gray-700 dark:text-gray-200">ĐVT</th>
                    <th class="text-right font-semibold text-gray-700 dark:text-gray-200">Nhập kho</th>
                    <th class="text-right font-semibold text-gray-700 dark:text-gray-200">Đã xuất</th>
                    <th class="text-right font-semibold text-gray-700 dark:text-gray-200">Còn lại</th>
                    <th class="text-left font-semibold text-gray-700 dark:text-gray-200">Dịch vụ</th>
                    <th class="text-right font-semibold text-gray-700 dark:text-gray-200">Phí DV còn lại</th>
                    <th class="text-left font-semibold text-gray-700 dark:text-gray-200">Xuất lần này</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($customerStock->items as $item)
                    @php
                        $sku = $item->orderItem->productSku;
                        $remainingQuantity = $item->remainingQuantity();
                    @endphp
                    <tr wire:key="release-stock-item-{{ $item->id }}" class="transition hover:bg-slate-50">
                        <td class="font-medium">{{ $sku->product->name }}</td>
                        <td>{{ $sku->sku_code }}</td>
                        <td>{{ $sku->product->unit }}</td>
                        <td class="text-right">{{ number_format($item->received_quantity) }}</td>
                        <td class="text-right">{{ number_format($item->released_quantity) }}</td>
                        <td class="text-right font-medium">{{ number_format($remainingQuantity) }}</td>
                        <td>
                            @forelse ($item->orderItem->services as $service)
                                <p class="font-medium text-blue-700">{{ $service->service_name }}</p>
                                <p class="text-xs text-slate-500">{{ number_format((float) $service->unit_price, 0, ',', '.') }}đ/SP</p>
                            @empty
                                <span class="text-slate-400">Không có</span>
                            @endforelse
                        </td>
                        <td class="text-right font-medium text-blue-700">
                            {{ number_format($remainingQuantity * $item->orderItem->services->sum(fn ($service): float => (float) $service->unit_price), 0, ',', '.') }}đ
                        </td>
                        <td>
                            <input
                                type="number"
                                min="0"
                                max="{{ $remainingQuantity }}"
                                step="1"
                                wire:model.live.debounce.300ms="quantities.{{ $item->id }}"
                                @disabled($remainingQuantity === 0)
                                class="release-quantity-input block w-32 rounded-lg bg-slate-50 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                            >
                            @error("quantities.{$item->id}")
                                <p class="pt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-500">Order này chưa có hàng tồn.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@error('quantities')
    <p class="pt-2 text-sm text-red-600">{{ $message }}</p>
@enderror

<div class="mt-3 grid gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-sm sm:grid-cols-3">
    <div>
        <p class="text-slate-500">Tiền sản phẩm dự kiến</p>
        <p class="mt-1 font-bold text-slate-900">{{ number_format($releaseProductPreview, 0, ',', '.') }}đ</p>
    </div>
    <div>
        <p class="text-slate-500">Tiền dịch vụ dự kiến</p>
        <p class="mt-1 font-bold text-blue-700">{{ number_format($releaseServicePreview, 0, ',', '.') }}đ</p>
    </div>
    <div>
        <p class="text-slate-500">Tạm tính trước phân bổ</p>
        <p class="mt-1 font-bold text-slate-900">{{ number_format($releaseProductPreview + $releaseServicePreview, 0, ',', '.') }}đ</p>
    </div>
</div>
