{{--
    Modal danh sách Order đang giữ SKU.
    Chỉ allocation ở trạng thái allocated được truyền từ ProductInventory nên danh sách
    phản ánh đúng lượng hàng hiện còn cấp cho các Order chưa hoàn tất.
--}}
<div
    x-show="modal === 'orders' && activeSku === {{ $sku->id }}"
    x-cloak
    x-on:click.self="modal = null; activeSku = null"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
>
    <div class="inventory-detail-modal max-h-[80vh] w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
        <div class="mb-4 flex items-center justify-between gap-4">
            <p class="text-base font-semibold text-slate-800">Order đang được cấp: {{ $sku->sku_code }}</p>
            <button
                type="button"
                x-on:click="modal = null; activeSku = null"
                class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                aria-label="Đóng"
            >
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        <div class="inventory-modal-scroll rounded-lg border border-slate-200 bg-white">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-slate-100 text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Khách hàng</th>
                        <th class="px-3 py-2 text-right">Số lượng</th>
                        <th class="px-3 py-2">Ngày cấp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sku->inventoryAllocations as $allocation)
                        <tr>
                            <td class="px-3 py-2 font-semibold">{{ $allocation->order?->order_code ?? 'Không có' }}</td>
                            <td class="px-3 py-2">{{ $allocation->order?->customer?->name ?? 'Không có' }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($allocation->quantity) }}</td>
                            <td class="px-3 py-2">{{ $allocation->allocated_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-5 text-center text-slate-500">SKU hiện không được cấp cho Order nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
