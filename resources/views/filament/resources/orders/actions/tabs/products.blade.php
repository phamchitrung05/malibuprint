<section class="mb-1 rounded-2xl border border-purple-100 bg-white p-5 shadow-sm"><h3 class="text-lg font-extrabold">Danh sách
        sản phẩm ({{ $order->items->count() }})</h3>
    <div class="mt-4 overflow-x-auto rounded-xl border-2 border-purple-100">
        <table class="w-full min-w-[600px] text-left text-sm">
            <thead class="border-b-2 border-purple-100 bg-slate-50">
            <tr>
                <th class="px-3 py-3">#</th>
                <th class="px-3 py-3">Sản phẩm</th>
                <th class="px-3 py-3">SL</th>
                <th class="px-3 py-3">Đơn giá</th>
                <th class="px-3 py-3">Thành tiền</th>
            </tr>
            </thead>
            <tbody class="divide-y-2 divide-purple-100">@foreach ($order->items as $index => $item)
                <tr>
                    <td class="px-3 py-3">{{ $index + 1 }}</td>
                    <td class="px-3 py-3 font-bold">{{ $item->productSku?->product?->name ?? 'Sản phẩm' }}</td>
                    <td class="px-3 py-3">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="px-3 py-3">{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</td>
                    <td class="px-3 py-3 font-bold">{{ number_format((float) $item->subtotal, 0, ',', '.') }}đ</td>
                </tr>
            @endforeach</tbody>
        </table>
    </div>
</section>
