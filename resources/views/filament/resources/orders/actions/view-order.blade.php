@php
    $statusLabels = ['pending' => 'Chờ xử lý', 'processing' => 'Đang thiết kế', 'completed' => 'Đang in', 'cancelled' => 'Đã hủy'];
@endphp

<div
    class="h-[calc(100dvh-10rem)] min-h-[520px] space-y-1 overflow-y-auto p-1 text-slate-900">
    <section class="rounded-2xl border border-slate-100 bg-sky-100 shadow-xs mb-4">
        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
            <div>
                <div class="flex flex-wrap items-center gap-3"><h2 class="text-2xl font-extrabold tracking-tight">
                        #{{ $order->order_code }}</h2><span
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm font-bold text-amber-600"><x-heroicon-o-clock
                            class="h-4 w-4"/>{{ $statusLabels[$order->status] ?? 'Chờ xử lý' }}</span></div>
                <p class="mt-2 text-sm text-slate-500"><b>Tạo ngày {{ $order->order_date?->format('d/m/Y H:i') }} </b><span
                        class="mx-2 text-slate-300">•</span><b> Cập nhật {{ $order->updated_at?->format('d/m/Y H:i') }}</b></p>
            </div>
            <div class="flex gap-2">
                <button type="button" title="In đơn hàng"
                        class="rounded-lg bg-white p-2.5 text-slate-600">
                    <x-heroicon-o-printer class="h-5 w-5"/>
                </button>
                <button type="button" title="Chia sẻ"
                        class="rounded-lg bg-white p-2.5 text-slate-600">
                    <x-heroicon-o-share class="h-5 w-5"/>
                </button>
            </div>
        </div>
    </section>

    <div class="mb-4">
        <div
            class="relative mt-4 grid grid-cols-3 px-5 pb-5 text-center text-xs font-bold text-slate-500 before:absolute before:left-[16.6667%] before:right-[16.6667%] before:top-4 before:h-0.5 before:-translate-y-1/2 before:bg-slate-200">
            <span class="absolute left-[16.6667%] top-4 z-0 h-0.5 bg-emerald-500" style="width: 20%"></span>
            <div class="relative z-10">
                <div
                    class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <x-heroicon-s-check class="h-5 w-5"/>
                </div>
                <b>Đơn mới</b><small class="block font-normal">{{ $order->order_date?->format('d/m H:i') }}</small>
            </div>
            <div class="relative z-10">
                <div
                    class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <x-heroicon-s-check class="h-5 w-5"/>
                </div>
                <b>Xử lý</b><small class="block font-normal">{{ $order->order_date?->format('d/m H:i') }}</small></div>
            <div class="relative z-10">
                <div
                    class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white ring-8 ring-blue-50">
                    <x-heroicon-s-printer class="h-5 w-5"/>
                </div>
                <b class="text-blue-600">Hoàn thành</b><small
                    class="block font-normal">{{ $order->updated_at?->format('d/m H:i') }}</small></div>
        </div>
    </div>

    <div class="mb-4">
        <livewire:order-detail-panel :order-id="$order->id"/>
    </div>

    @php($money = fn ($value): string => number_format((float) $value, 0, ',', '.').'đ')
    <div class="mb-1 grid gap-3 lg:grid-cols-2 mb-4">
        <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><h3 class="font-extrabold">Ghi chú đơn hàng</h3>
                <x-heroicon-o-pencil class="h-4 w-4 text-slate-500"/>
            </div>
            <div
                class="mt-4 whitespace-pre-line rounded-lg bg-lime-100 font-medium p-4 text-sm leading-7 text-slate-700">{{ $order->note ?: "• Màu sắc theo file thiết kế đã gửi\n• Cán mờ 2 mặt\n• Giao hàng trước thứ 7" }}</div>
        </section>
        <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm"><h3 class="font-extrabold">Tổng
                tiền</h3>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tạm tính</dt>
                    <dd>{{ $money($order->subtotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Giảm giá</dt>
                    <dd>{{ $money($order->discount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Phí vận chuyển</dt>
                    <dd>0đ</dd>
                </div>
                <div class="flex justify-between border-t-2 border-slate-100 pt-3 text-base font-extrabold">
                    <dt>Tổng cộng</dt>
                    <dd class="text-xl text-blue-600">{{ $money($order->total_amount) }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <footer class="flex flex-wrap gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <button type="button" wire:click="$dispatch('order-cancel')"
                class="min-w-[120px] flex-1 rounded-lg border border-slate-100 bg-slate-50 px-5 py-3 font-bold text-slate-700">
            Hủy đơn
        </button>
        <button type="button" wire:click="$dispatch('order-complete')"
                class="min-w-[180px] flex-[2] rounded-lg bg-blue-600 px-5 py-3 font-bold text-white shadow-sm transition hover:bg-blue-700">
            Cập nhật trạng thái
            <x-heroicon-o-chevron-down class="ml-2 inline h-4 w-4"/>
        </button>
    </footer>
</div>
