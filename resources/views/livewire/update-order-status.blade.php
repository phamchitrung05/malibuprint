@php
    $orderStatuses = \App\Support\StatusApp::values('order.status');
    $productionStages = array_filter($orderStatuses, fn (array $status): bool => $status['show_in_progress'] ?? true);
    $currentStatusIndex = array_search($order->status, array_keys($productionStages), true);
    $currentStep = $currentStatusIndex === false ? 0 : $currentStatusIndex + 1;
    $currentStatus = $orderStatuses[$order->status] ?? [];
    $progressWidth = match ($currentStep) {
        2 => '33.3333%',
        3 => '66.6667%',
        default => '0%',
    };
@endphp

<div class="space-y-4 text-left order-view-modal min-h-0 flex-1 max-h-[80vh] overflow-y-auto bg-slate-50/60 p-5">
    <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-base font-bold text-slate-950">Đơn hàng #{{ $order->order_code }}</p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $order->customer?->name ?? 'Không xác định' }}
                    <span class="px-1">•</span>
                    {{ $order->order_date?->format('d/m/Y H:i') ?? '--/--' }}
                </p>
            </div>
            <span class="rounded-lg bg-white px-3 py-1.5 text-sm font-bold text-blue-600 ring-1 ring-slate-200">
                {{ number_format((float) $order->total_amount, 0, ',', '.') }}đ
            </span>
        </div>
    </section>

    <section class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
        <div class="mb-4 flex items-center gap-2">
            <div class="flex size-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600"><x-heroicon-o-list-bullet class="size-4"/></div>
            <p class="text-sm font-semibold text-slate-800">Tiến trình sản xuất hiện tại</p>
        </div>
        <div class="relative">
            <div class="absolute left-[16.6667%] right-[16.6667%] top-4 h-0.5 bg-slate-200"></div>
            <div class="absolute left-[16.6667%] top-4 h-0.5 {{ $currentStatus['progress_class'] ?? 'bg-slate-400' }}" style="width: {{ $progressWidth }}"></div>
            <div class="relative grid grid-cols-3">
                @foreach ($productionStages as $stage)
                    @php
                        $step = $loop->iteration;
                        $isDone = $currentStep > $step;
                        $isCurrent = $currentStep === $step;
                        $isReached = $isDone || $isCurrent;
                    @endphp
                    <div class="flex flex-col items-center">
                        <div @class([
                            'z-10 flex size-8 items-center justify-center rounded-full ring-4 ring-white',
                            ($stage['progress_class'] ?? 'bg-slate-400').' text-white' => $isReached,
                            'bg-white text-slate-400 ring-slate-100' => ! $isReached,
                        ])>
                            @if ($isReached)<x-heroicon-s-check class="size-4"/>@else<span class="size-2 rounded-full bg-current"></span>@endif
                        </div>
                        <span class="mt-2 text-xs font-semibold {{ $isReached ? ($stage['label_classes'] ?? 'text-slate-700') : 'text-slate-400' }}">{{ $stage['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('filament.resources.orders.actions.status-list')

</div>
