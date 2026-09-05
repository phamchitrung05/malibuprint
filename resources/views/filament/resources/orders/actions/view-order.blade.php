@php
    $orderStatuses = config('order.statuses');
    $currentStatus = $orderStatuses[$order->status] ?? [
        'label' => 'Đã hủy',
        'badge_classes' => 'bg-red-500 text-white',
        'progress_class' => 'bg-red-500',
    ];
    $statusFlow = array_values(array_filter($orderStatuses, fn (array $status): bool => $status['show_in_progress'] ?? true));
    $statusStep = array_search($order->status, array_keys($orderStatuses), true);
    $statusStep = $statusStep === false || $order->status === 'cancelled' ? 0 : $statusStep + 1;
    $progressWidth = match ($statusStep) {
        1 => '0%',
        2 => '33.3333%',
        3 => '66.6667%',
        default => '0%',
    };
@endphp

<div
    class="h-[calc(100dvh-10rem)] min-h-[520px] space-y-1 overflow-y-auto p-1 text-slate-900">
    <section class=" border-b border-gray-200 mb-4">
        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
            <div>
                <div class="flex flex-wrap items-center gap-3"><h2 class="text-2xl font-extrabold tracking-tight">
                        #{{ $order->order_code }}</h2><span
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold {{ $currentStatus['badge_classes'] }}"><x-heroicon-o-clock
                        class="h-4 w-4"/>{{ $currentStatus['label'] }}</span></div>
                <p class="mt-2 text-sm text-slate-500"><b>Tạo
                        ngày {{ $order->order_date?->format('d/m/Y H:i') }} </b><span
                        class="mx-2 text-slate-300">•</span><b> Cập
                        nhật {{ $order->updated_at?->format('d/m/Y H:i') }}</b></p>
            </div>
            <div class="flex gap-2">
                <button type="button" title="In đơn hàng"
                        class="rounded-lg bg-white p-2.5 text-slate-600">
                    <x-heroicon-o-printer class="h-5 w-5"/>
                </button>
            </div>
        </div>
    </section>

    <div class="mb-4 bg-gray-100 p-2 rounded-2xl">
        <div class="relative grid grid-cols-3 text-center text-xs font-bold text-slate-500 before:absolute before:left-[16.6667%] before:right-[16.6667%] before:top-4 before:h-0.5 before:-translate-y-1/2 before:bg-slate-200">
            <span class="absolute left-[16.6667%] top-4 z-0 h-0.5 {{ $currentStatus['progress_class'] }}" style="width: {{ $progressWidth }}"></span>
            @foreach ($statusFlow as $index => $status)
                @php($stepNumber = $index + 1)
                @php($isReached = $stepNumber <= $statusStep)
                <div class="relative z-10">
                    <div class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full {{ $isReached ? $status['badge_classes'] : 'bg-slate-300 text-slate-400' }} {{ $stepNumber === $statusStep ? 'ring-8 ring-blue-100' : '' }}">
                        @if ($stepNumber === 3)
                            <x-heroicon-s-printer class="h-5 w-5" />
                        @else
                            <x-heroicon-s-check class="h-5 w-5" />
                        @endif
                    </div>
                    <b class="text-sm {{ $stepNumber === $statusStep ? ($status['label_classes'] ?? '') : '' }}">{{ $status['label'] }}</b>
                    <span class="test-sm block font-normal">{{ $isReached ? $order->order_date?->format('d/m H:i') : '--/--' }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-4 rounded-2xl bg-gray-100 p-2 text-center">
        <div>
            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full {{ $order->is_paid ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400' }}">
                <x-heroicon-o-banknotes class="h-5 w-5" />
            </div>
            <b class="text-sm {{ $order->is_paid ? 'text-emerald-600' : 'text-slate-500' }}">Đã thanh toán</b>
            <span class="block text-xs font-normal text-slate-400">--/--</span>
        </div>
        <div>
            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full {{ $order->is_delivered ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-400' }}">
                <x-heroicon-o-truck class="h-5 w-5" />
            </div>
            <b class="text-sm {{ $order->is_delivered ? 'text-blue-600' : 'text-slate-500' }}">Đã giao hàng</b>
            <span class="block text-xs font-normal text-slate-400">--/--</span>
        </div>
    </div>

    <div class="mb-4">
        <livewire:order-detail-panel :order-id="$order->id"/>
    </div>

    @php($money = fn ($value): string => number_format((float) $value, 0, ',', '.').'đ')
    <div class="mb-1 grid gap-3 lg:grid-cols-2 mb-4">
        <section class="rounded-2xl border border-gray-100 bg-white shadow-2xs shadow-gray-200">
            <h3 class="border-b border-gray-200 p-3 text-lg font-extrabold">Ghi chú đơn hàng</h3>
            <div
                class="p-5 mt-4 whitespace-pre-line rounded-lg bg-lime-100 font-medium p-4 text-sm leading-7 text-slate-700">{{ $order->note ?: "• Màu sắc theo file thiết kế đã gửi\n• Cán mờ 2 mặt\n• Giao hàng trước thứ 7" }}</div>
        </section>
        <section class="rounded-2xl border border-gray-100 bg-gray-50 shadow-2xs shadow-gray-200"><h3
                class="border-b border-gray-200 p-3 text-lg font-extrabold">Tổng
                tiền</h3>
            <dl class="p-5 mt-4 grid gap-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Tạm tính</dt>
                    <dd class="text-base font-bold">{{ $money($order->subtotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-500">Giảm giá</dt>
                    <dd class="text-base font-bold">- {{ $money($order->discount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Phí vận chuyển</dt>
                    <dd class="text-base font-bold">0đ</dd>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-extrabold">
                    <dt>Tổng cộng</dt>
                    <dd class="text-xl text-blue-600">{{ $money($order->total_amount) }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <footer class="flex flex-wrap gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-2xs shadow-gray-200">
        <button type="button" wire:click="$dispatch('order-cancel')"
                class="min-w-[120px] flex-1 rounded-lg border border-slate-100 bg-slate-50 px-5 py-3 font-bold text-slate-700">
            Hủy đơn
        </button>
        <button type="button" wire:click="$dispatch('order-complete')"
                class="min-w-[180px] flex-[2] rounded-lg bg-blue-600 px-5 py-3 font-bold text-white shadow-2xs shadow-gray-200 transition hover:bg-blue-700">
            Cập nhật trạng thái
            <x-heroicon-o-chevron-down class="ml-2 inline h-4 w-4"/>
        </button>
    </footer>
</div>
