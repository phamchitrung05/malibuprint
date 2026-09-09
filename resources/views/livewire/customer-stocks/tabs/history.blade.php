<div class="overflow-hidden rounded-xl border border-slate-200">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1200px] border-collapse">
            <thead>
                <tr class="bg-slate-100/90 text-left">
                    <th>Mã phiếu</th>
                    <th>Ngày xuất</th>
                    <th>Sản phẩm</th>
                    <th>SKU</th>
                    <th class="text-right">Số lượng</th>
                    <th class="text-right">Tiền SP</th>
                    <th class="text-right">Tiền DV</th>
                    <th class="text-right">Giảm giá</th>
                    <th class="text-right">Điều chỉnh</th>
                    <th class="text-right">Phí giao hàng</th>
                    <th class="text-right">Tổng phiếu</th>
                    <th>Người xuất</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($customerStock->releases as $release)
                    <tr wire:key="release-history-{{ $release->id }}" class="transition hover:bg-slate-50">
                        <td class="font-medium">{{ $release->release_code }}</td>
                        <td>{{ $release->released_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @foreach ($release->items as $releaseItem)
                                <p>{{ $releaseItem->customerStockItem->orderItem->productSku->product->name }}</p>
                            @endforeach
                        </td>
                        <td>
                            @foreach ($release->items as $releaseItem)
                                <p>{{ $releaseItem->customerStockItem->orderItem->productSku->sku_code }}</p>
                            @endforeach
                        </td>
                        <td class="text-right">
                            @foreach ($release->items as $releaseItem)
                                <p>{{ number_format($releaseItem->quantity) }}</p>
                            @endforeach
                        </td>
                        <td class="text-right">{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</td>
                        <td class="text-right font-medium text-blue-700">{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</td>
                        <td class="text-right text-red-600">-{{ number_format((float) $release->allocated_discount, 0, ',', '.') }}đ</td>
                        <td class="text-right text-slate-500">
                            {{ (float) $release->reconciliation_adjustment > 0 ? '+' : '' }}{{ number_format((float) $release->reconciliation_adjustment, 0, ',', '.') }}đ
                        </td>
                        <td class="text-right">{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}đ</td>
                        <td class="text-right font-medium">{{ number_format((float) $release->total_amount, 0, ',', '.') }}đ</td>
                        <td>{{ $release->creator?->name ?? 'Hệ thống' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="py-8 text-center text-slate-500">Chưa có phiếu xuất hàng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
