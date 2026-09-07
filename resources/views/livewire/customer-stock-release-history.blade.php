<div class="space-y-5">
    {{-- Payment chỉ được tạo từ nút xác nhận của từng phiếu, vì vậy trạng thái thanh toán không bị nhập tay. --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <p class="font-semibold text-gray-950 dark:text-white">{{ $customerStock->customer->name }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Order: {{ $customerStock->order->order_code }}</p>
    </div>

    <label class="block space-y-1">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ghi chú khi xác nhận thu tiền</span>
        <input wire:model="paymentNote" type="text" maxlength="500" class="block w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
        @error('paymentNote') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
        @error('payment') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
    </label>

    <div class="space-y-3">
        @forelse ($customerStock->releases as $release)
            <article wire:key="stock-release-{{ $release->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-950 dark:text-white">{{ $release->release_code }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $release->released_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-950 dark:text-white">{{ number_format((float) $release->total_amount, 0, ',', '.') }}đ</p>
                        <p class="text-sm {{ $release->payment ? 'text-success-600' : 'text-warning-600' }}">
                            {{ $release->payment ? 'Đã xác nhận thu tiền' : 'Chưa xác nhận thu tiền' }}
                        </p>
                    </div>
                </div>

                <div class="pt-3 text-sm text-gray-600 dark:text-gray-300">
                    @foreach ($release->items as $item)
                        <p>{{ $item->customerStockItem->orderItem->productSku->sku_code }}: {{ $item->quantity }} sản phẩm</p>
                    @endforeach
                </div>

                @if (! $release->payment)
                    <div class="flex justify-end pt-3">
                        <button
                            type="button"
                            wire:click="confirmPayment({{ $release->id }})"
                            wire:confirm="Xác nhận đã thu đủ tiền của phiếu xuất này?"
                            wire:loading.attr="disabled"
                            wire:target="confirmPayment"
                            class="rounded-lg bg-success-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Xác nhận đã thu tiền
                        </button>
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                Chưa có phiếu xuất hàng.
            </p>
        @endforelse
    </div>
</div>
