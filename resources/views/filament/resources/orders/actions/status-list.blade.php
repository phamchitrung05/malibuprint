@php
    $pendingStatus = \App\Support\StatusApp::value('order.status', 'pending');
    $processingStatus = \App\Support\StatusApp::value('order.status', 'processing');
    $completedStatus = \App\Support\StatusApp::value('order.status', 'completed');
    $cancelledStatus = \App\Support\StatusApp::value('order.status', 'cancelled');
    $canFulfill = $order->status === $completedStatus;
@endphp

<div x-data="{ cancelConfirmationOpen: false }" class="space-y-3 px-5">
    <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5 text-xs text-blue-700">
        Quy trình sản xuất chỉ đi tới bước kế tiếp. Thanh toán và giao hàng được xác nhận độc lập.
    </div>

    @if ($order->status === $pendingStatus)
        <button type="button" wire:click="startProcessing" wire:loading.attr="disabled" wire:target="startProcessing" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
            Bắt đầu xử lý
        </button>
    @elseif ($order->status === $processingStatus)
        <button type="button" wire:click="completeProduction" wire:loading.attr="disabled" wire:target="completeProduction" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
            Hoàn thành sản xuất
        </button>
    @else
        <p class="rounded-lg bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-600">
            {{ $order->status === $cancelledStatus ? 'Đơn hàng đã hủy.' : 'Quy trình sản xuất đã hoàn thành.' }}
        </p>
    @endif

    @if ($order->status !== $cancelledStatus && $order->fulfillment_mode === \App\Enums\FulfillmentMode::CustomerStock)
        <div class="rounded-lg border border-cyan-200 bg-cyan-50 p-3 text-sm text-cyan-800 dark:border-cyan-900 dark:bg-cyan-950/40 dark:text-cyan-200">
            Đơn này xuất nhiều đợt. Sau khi hoàn thành sản xuất, hãy quản lý giao hàng và xác nhận thu tiền tại Customer Stock.
        </div>
    @elseif ($order->status !== $cancelledStatus)
        <div class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-2">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-800">Thanh toán</p>
                <p class="text-xs text-slate-500">Còn lại: {{ number_format($remainingAmount, 0, ',', '.') }}đ</p>
                @if ($remainingAmount > 0)
                    @error('payment') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="button" wire:click="confirmPayment" wire:loading.attr="disabled" wire:target="confirmPayment" @disabled(! $canFulfill) class="w-full rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:opacity-60">Xác nhận đã thu toàn bộ</button>
                @else
                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">Đã thanh toán đủ</p>
                @endif
            </div>

            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-800">Giao hàng</p>
                <p class="text-xs text-slate-500">{{ $order->is_delivered ? 'Đơn hàng đã được giao.' : 'Xác nhận đơn đã giao đến khách hàng.' }}</p>
                @if (! $order->is_delivered)
                    <button type="button" wire:click="confirmShipping" wire:loading.attr="disabled" wire:target="confirmShipping" @disabled(! $canFulfill) class="w-full rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:opacity-60">Xác nhận đã giao</button>
                @else
                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">Đã giao hàng</p>
                @endif
            </div>
        </div>

        @if (! $canFulfill)
            <p class="text-xs font-medium text-slate-500">Hoàn thành sản xuất để mở chức năng thu tiền và giao hàng.</p>
        @endif
    @endif

    @if (in_array($order->status, [$pendingStatus, $processingStatus], true))
        <div class="rounded-lg border border-red-100 bg-red-50/50 p-3">
            <button type="button" x-on:click="cancelConfirmationOpen = true" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">Hủy đơn hàng</button>
        </div>

        <x-ui.confirmation-modal
            state="cancelConfirmationOpen"
            close="cancelConfirmationOpen = false"
            confirm-action="$wire.cancelOrder()"
            title="Xác nhận hủy đơn hàng"
            :description="'Đơn hàng #'.$order->order_code.' sẽ bị hủy và toàn bộ tồn kho đã giữ cho đơn sẽ được hoàn lại. Hành động này không thể hoàn tác.'"
            confirm-label="Xác nhận hủy"
            cancel-label="Giữ đơn hàng"
            wire-target="cancelOrder"
        />
    @endif
</div>
