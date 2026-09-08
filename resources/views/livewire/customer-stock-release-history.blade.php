<div x-data="{ confirmReleaseId: null }" class="mt-5 overflow-hidden rounded-xl border border-slate-200">
    {{-- Payment chỉ được tạo khi người dùng xác nhận phiếu xuất tương ứng. --}}
    <div class="overflow-y-auto inventory-modal-scroll h22vh">
        <table class="w-full border-collapse">
            <thead class="bg-slate-100/90">
                <tr>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">STT</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Mã phiếu thu</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Sản phẩm</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Product SKU</th>
                    <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">SL xuất</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Ngày thu</th>
                    <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">Phí giao hàng (đ)</th>
                    <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">Số tiền (đ)</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Người thu</th>
                    <th class="px-5 py-4 text-center text-sm font-semibold text-slate-600">Thao tác</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($customerStock->releases as $release)
                    @php($payment = $release->payment)
                    <tr wire:key="stock-release-payment-{{ $release->id }}" class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 text-sm text-slate-700">{{ $loop->iteration }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">
                            {{ $payment ? 'PT'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) : 'Chưa có' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-700">
                            <div class="space-y-1">
                                @foreach ($release->items as $item)
                                    <div>{{ $item->customerStockItem?->orderItem?->productSku?->product?->name ?? 'Không xác định' }}</div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-700">
                            <div class="space-y-1">
                                @foreach ($release->items as $item)
                                    <div class="font-medium">{{ $item->customerStockItem?->orderItem?->productSku?->sku_code ?? 'Không xác định' }}</div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">
                            <div class="space-y-1">
                                @foreach ($release->items as $item)
                                    <div>{{ number_format($item->quantity) }}</div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @if ($payment)
                                <div class="text-sm font-medium text-slate-900">{{ $payment->payment_date->format('d/m/Y') }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $payment->payment_date->format('H:i') }}</div>
                            @else
                                <span class="text-sm text-slate-400">Chưa thu</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right text-sm text-slate-700">
                            {{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">
                            {{ number_format((float) $release->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-700">
                            {{ $payment?->confirmer?->name ?? 'Chưa xác nhận' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-center">
                                @if ($payment)
                                    <button
                                        type="button"
                                        disabled
                                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-100 px-4 text-sm font-semibold text-slate-400"
                                    >
                                        <x-heroicon-o-check class="size-4" />
                                        Đã xác nhận
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        x-on:click="confirmReleaseId = {{ $release->id }}"
                                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-emerald-500 px-4 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-50"
                                    >
                                        <x-heroicon-o-check class="size-4" />
                                        Xác nhận
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-5 py-8 text-center text-sm text-slate-500">
                            Chưa có phiếu xuất hàng cần xác nhận.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal xác nhận dùng giao diện ứng dụng, không dùng alert native của trình duyệt. --}}
    <div
        x-cloak
        x-show="confirmReleaseId !== null"
        x-on:keydown.escape.window="confirmReleaseId = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-payment-title"
    >
        <div x-on:click.outside="confirmReleaseId = null" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex size-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <x-heroicon-o-check class="size-6" />
            </div>
            <h2 id="confirm-payment-title" class="mt-4 text-lg font-bold text-slate-900">Xác nhận phiếu thu</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Bạn chắc chắn đã thu đủ tiền của phiếu xuất này?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="confirmReleaseId = null" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Hủy</button>
                <button
                    type="button"
                    x-on:click="$wire.confirmPayment(confirmReleaseId); confirmReleaseId = null"
                    wire:loading.attr="disabled"
                    wire:target="confirmPayment"
                    class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-50"
                >
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>
