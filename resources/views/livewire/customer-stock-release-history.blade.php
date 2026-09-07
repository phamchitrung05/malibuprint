<div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
    {{-- Payment chỉ được tạo khi người dùng xác nhận phiếu xuất tương ứng. --}}
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] border-collapse">
            <thead class="bg-slate-100/90">
                <tr>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">STT</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Mã phiếu thu</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Ngày thu</th>
                    <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">Số tiền (đ)</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Phương thức</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Người thu</th>
                    <th class="px-5 py-4 text-left text-sm font-semibold text-slate-600">Trạng thái</th>
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
                        <td class="px-5 py-4">
                            @if ($payment)
                                <div class="text-sm font-medium text-slate-900">{{ $payment->payment_date->format('d/m/Y') }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $payment->payment_date->format('H:i') }}</div>
                            @else
                                <span class="text-sm text-slate-400">Chưa thu</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">
                            {{ number_format((float) $release->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-500">Chưa cập nhật</td>
                        <td class="px-5 py-4 text-sm text-slate-700">
                            {{ $payment?->confirmer?->name ?? 'Chưa xác nhận' }}
                        </td>
                        <td class="px-5 py-4">
                            @if ($payment)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-600">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Đã xác nhận
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-600">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    Chờ xác nhận
                                </span>
                            @endif
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
                                        wire:click="confirmPayment({{ $release->id }})"
                                        wire:confirm="Xác nhận đã thu đủ tiền của phiếu xuất này?"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmPayment({{ $release->id }})"
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
                        <td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">
                            Chưa có phiếu xuất hàng cần xác nhận.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
