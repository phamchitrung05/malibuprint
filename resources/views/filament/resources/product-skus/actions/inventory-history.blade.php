<div class="overflow-hidden rounded-xl border border-slate-200">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
                <tr>
                    <th class="px-4 py-3">Thời gian</th>
                    <th class="px-4 py-3">Nghiệp vụ</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3 text-right">Thay đổi</th>
                    <th class="px-4 py-3 text-right">Tồn trước</th>
                    <th class="px-4 py-3 text-right">Tồn sau</th>
                    <th class="px-4 py-3">Người thực hiện</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($movements as $movement)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ \App\Support\StatusApp::label('inventory_movement.type', $movement->type) }}</td>
                        <td class="px-4 py-3">{{ $movement->order?->order_code ?? 'Không có' }}</td>
                        <td @class([
                            'px-4 py-3 text-right font-semibold',
                            'text-emerald-600' => $movement->quantity > 0,
                            'text-red-600' => $movement->quantity < 0,
                        ])>
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($movement->balance_before) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($movement->balance_after) }}</td>
                        <td class="px-4 py-3">{{ $movement->creator?->name ?? 'Hệ thống' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">SKU chưa có giao dịch tồn kho.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
