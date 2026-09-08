{{--
    Modal lịch sử biến động của một SKU.
    Dữ liệu được nạp sẵn từ ProductInventory để modal chỉ chịu trách nhiệm hiển thị.
    Bộ lọc ngày dùng ngày tạo movement và bao gồm cả ngày bắt đầu/kết thúc.
--}}
<div
    x-show="modal === 'history' && activeSku === {{ $sku->id }}"
    x-cloak
    x-on:click.self="modal = null; activeSku = null"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
>
    <div class="inventory-detail-modal max-h-[80vh] w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
        <div class="mb-4 flex items-center justify-between gap-4">
            <p class="text-base font-semibold text-slate-800">Lịch sử xuất nhập: {{ $sku->sku_code }}</p>
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
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-100 text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Thời gian</th>
                        <th class="px-3 py-2">Nghiệp vụ</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Lý do</th>
                        <th class="px-3 py-2 text-right">Thay đổi</th>
                        <th class="px-3 py-2 text-right">Tồn sau</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sku->inventoryMovements as $movement)
                        <tr>
                            <td class="px-3 py-2">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ \App\Support\StatusApp::label('inventory_movement.type', $movement->type) }}</td>
                            <td class="px-3 py-2">{{ $movement->order?->order_code ?? 'Không có' }}</td>
                            <td class="px-3 py-2">{{ $movement->reason ?: 'Không có' }}</td>
                            <td @class([
                                'px-3 py-2 text-right font-semibold',
                                'text-emerald-600' => $movement->quantity > 0,
                                'text-red-600' => $movement->quantity < 0,
                            ])>
                                {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($movement->balance_after) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-5 text-center text-slate-500">SKU chưa có giao dịch tồn kho trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
