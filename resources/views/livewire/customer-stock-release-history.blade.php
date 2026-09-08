<div x-data="{ confirmReleaseId: null, expandedReleaseId: null }" class="mt-5 overflow-hidden rounded-xl border border-slate-200">
    {{-- Payment chỉ được tạo khi người dùng xác nhận phiếu xuất tương ứng. --}}
    <div class="overflow-auto inventory-modal-scroll h22vh">
        <table class="w-full min-w-[900px] border-collapse">
            <thead class="bg-slate-100/90">
                <tr>
                    <th class="w-12 px-3 py-4"><span class="sr-only">Mở rộng</span></th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">STT</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Mã phiếu thu</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Sản phẩm</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Product SKU</th>
                    <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">SL xuất</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Ngày thu</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Người thu</th>
                    <th class="sticky right-0 z-10 bg-slate-100 px-5 py-4 text-center text-sm font-semibold text-slate-600 shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)]">Thao tác</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($customerStock->releases as $release)
                    @php($payment = $release->payment)
                    <tr wire:key="stock-release-payment-{{ $release->id }}" class="group transition hover:bg-slate-50">
                        <td class="px-3 py-4 text-center">
                            <button
                                type="button"
                                title="Xem chi tiết số tiền"
                                x-on:click="expandedReleaseId = expandedReleaseId === {{ $release->id }} ? null : {{ $release->id }}"
                                class="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
                            >
                                <x-heroicon-o-chevron-down class="size-4 transition" x-bind:class="expandedReleaseId === {{ $release->id }} ? 'rotate-180' : ''"/>
                                <span class="sr-only">Mở rộng phiếu</span>
                            </button>
                        </td>
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
                        <td class="px-5 py-4 text-sm text-slate-700">
                            {{ $payment?->confirmer?->name ?? 'Chưa xác nhận' }}
                        </td>
                        <td class="sticky right-0 z-10 bg-white px-5 py-4 shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)] transition group-hover:bg-slate-50">
                            <div class="flex justify-center gap-2">
                                @if ($payment)
                                    <button
                                        type="button"
                                        title="Đã xác nhận"
                                        disabled
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                                    >
                                        <x-heroicon-o-check class="size-4" />
                                        <span class="sr-only">Đã xác nhận</span>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        title="Xác nhận phiếu thu"
                                        x-on:click="confirmReleaseId = {{ $release->id }}"
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-emerald-500 text-white transition hover:bg-emerald-600 disabled:opacity-50"
                                    >
                                        <x-heroicon-o-check class="size-4" />
                                        <span class="sr-only">Xác nhận</span>
                                    </button>
                                @endif

                                @if ($payment)
                                    <a
                                        href="{{ route('stock-releases.receipt.print', $release) }}"
                                        target="_blank"
                                        rel="noopener"
                                        title="In phiếu thu"
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700"
                                    >
                                        <x-heroicon-o-printer class="size-4"/>
                                        <span class="sr-only">In phiếu thu</span>
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        title="Xác nhận phiếu trước khi in"
                                        disabled
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-slate-100 text-slate-400"
                                    >
                                        <x-heroicon-o-printer class="size-4"/>
                                        <span class="sr-only">Chưa thể in</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr
                        x-cloak
                        x-show="expandedReleaseId === {{ $release->id }}"
                        wire:key="stock-release-payment-detail-{{ $release->id }}"
                        class="bg-slate-50/80"
                    >
                        <td colspan="9" class="px-5 py-4">
                            {{-- Các khoản tiền được chuyển khỏi bảng chính để bảng gọn và dễ đọc trên màn hình nhỏ. --}}
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <p class="text-xs font-medium text-slate-500">Tiền sản phẩm</p>
                                    <p class="mt-1 text-base font-bold text-slate-900">{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</p>
                                </div>
                                <div class="rounded-lg border border-blue-100 bg-blue-50/60 p-3">
                                    <p class="text-xs font-medium text-blue-600">Tiền dịch vụ</p>
                                    <p class="mt-1 text-base font-bold text-blue-700">{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <p class="text-xs font-medium text-slate-500">Phí giao hàng</p>
                                    <p class="mt-1 text-base font-bold text-slate-900">{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}đ</p>
                                </div>
                                <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-3">
                                    <p class="text-xs font-medium text-emerald-600">Số tiền</p>
                                    <p class="mt-1 text-base font-bold text-emerald-700">{{ number_format((float) $release->total_amount, 0, ',', '.') }}đ</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500">
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
