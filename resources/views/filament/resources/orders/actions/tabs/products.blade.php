<section class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                <x-heroicon-o-cube class="size-4"/>
            </div>
            <h3 class="text-sm font-bold text-slate-900">
                Danh sách sản phẩm
                <span class="font-normal text-slate-400">({{ $order->items->count() }})</span>
            </h3>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
            <tr>
                <th class="px-5 py-3">#</th>
                <th class="px-3 py-3">Sản phẩm</th>
                <th class="px-3 py-3">SKU</th>
                <th class="px-3 py-3 text-right">SL</th>
                <th class="px-3 py-3 text-right">Đơn giá</th>
                <th class="px-3 py-3">Dịch vụ</th>
                <th class="px-5 py-3 text-right">Tiền sản phẩm</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($order->items as $index => $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4 text-slate-400">{{ $index + 1 }}</td>
                    <td class="px-3 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-500">
                                <x-heroicon-o-cube class="size-5"/>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800">{{ $item->productSku?->product?->name ?? 'Sản phẩm' }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $item->productSku?->product?->note ?? 'Chưa có mô tả' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-4 text-slate-600">{{ $item->productSku?->sku_code ?? '---' }}</td>
                    <td class="px-3 py-4 text-right font-medium text-slate-700">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="px-3 py-4 text-right text-slate-700">{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</td>
                    <td class="px-3 py-4">
                        @forelse ($item->services as $service)
                            <div class="space-y-0.5">
                                <p class="font-semibold text-blue-700">{{ $service->service_name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ number_format($service->quantity, 0, ',', '.') }} × {{ number_format((float) $service->unit_price, 0, ',', '.') }}đ
                                    = {{ number_format((float) $service->subtotal, 0, ',', '.') }}đ
                                </p>
                            </div>
                        @empty
                            <span class="text-slate-400">Không có</span>
                        @endforelse
                    </td>
                    <td class="px-5 py-4 text-right font-semibold text-slate-800">{{ number_format((float) $item->subtotal, 0, ',', '.') }}đ</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">Đơn hàng chưa có sản phẩm.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
