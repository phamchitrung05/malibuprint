<div class="space-y-5">
    {{-- Giao diện được giữ tối giản; toàn bộ kiểm tra số lượng vẫn được thực hiện lại trong service phía server. --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm text-gray-500 dark:text-gray-400">Khách hàng</p>
        <p class="font-semibold text-gray-950 dark:text-white">{{ $customerStock->customer->name }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Order: {{ $customerStock->order->order_code }}</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Sản phẩm</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">SKU</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Nhập kho</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Đã xuất</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Còn lại</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Xuất lần này</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-transparent">
                @foreach ($customerStock->items as $item)
                    @php($remainingQuantity = $item->remainingQuantity())
                    <tr wire:key="customer-stock-item-{{ $item->id }}">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $item->orderItem->productSku->product->name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $item->orderItem->productSku->sku_code }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $item->received_quantity }}</td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $item->released_quantity }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $remainingQuantity }}</td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                min="0"
                                max="{{ $remainingQuantity }}"
                                step="1"
                                wire:model="quantities.{{ $item->id }}"
                                @disabled($remainingQuantity === 0)
                                class="block w-28 rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                            @error("quantities.{$item->id}")
                                <p class="pt-1 text-xs text-danger-600">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @error('quantities')
        <p class="text-sm text-danger-600">{{ $message }}</p>
    @enderror

    <label class="block space-y-1">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ghi chú phiếu xuất</span>
        <textarea wire:model="note" rows="3" maxlength="1000" class="block w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
        @error('note') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
    </label>

    <div class="flex justify-end">
        <button
            type="button"
            wire:click="release"
            wire:loading.attr="disabled"
            wire:target="release"
            @disabled($customerStock->closed_at !== null)
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
        >
            Tạo phiếu xuất hàng
        </button>
    </div>
</div>
