@php
    $orderStatuses = config('order.statuses');
    $currentStatus = $orderStatuses[$order->status] ?? [
        'label' => 'Đã hủy',
        'badge_classes' => 'bg-red-500 text-white',
    ];
    $statusFlow = array_filter($orderStatuses, fn (array $status): bool => $status['show_in_progress'] ?? true);
    $statusIndex = array_search($order->status, array_keys($statusFlow), true);
    $statusStep = $statusIndex === false ? 0 : $statusIndex + 1;
    $progressWidth = match ($statusStep) {
        2 => '33.3333%',
        3 => '66.6667%',
        default => '0%',
    };
    $tabs = [
        'info' => ['label' => 'Thông tin chung'],
        'products' => ['label' => 'Sản phẩm', 'count' => $order->items->count()],
        'payment' => ['label' => 'Thanh toán', 'count' => $order->payments->count()],
        'shipping' => ['label' => 'Giao hàng'],
        'history' => ['label' => 'Lịch sử'],
        'note' => ['label' => 'Ghi chú'],
        'attachments' => ['label' => 'Tệp đính kèm', 'count' => $order->attachments->count()],
    ];
@endphp

<div
    x-data="{ activeTab: 'info' }"
    class="flex min-h-[520px] flex-col overflow-hidden text-slate-900"
>
    {{-- Header và tiến trình là thông tin chung nên luôn hiển thị khi chuyển tab. --}}
    <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Chi tiết đơn hàng #{{ $order->order_code }}
                </h2>
                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold {{ $currentStatus['badge_classes'] }}">
                    <span class="size-1.5 rounded-full bg-current opacity-70"></span>
                    {{ $currentStatus['label'] }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Tạo ngày {{ $order->order_date?->format('d/m/Y H:i') ?? '--/--' }}
                <span class="mx-2">•</span>
                Cập nhật lần cuối {{ $order->updated_at?->format('d/m/Y H:i') ?? '--/--' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                <x-heroicon-o-printer class="size-4"/>
                In đơn hàng
            </button>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                <x-heroicon-o-document-duplicate class="size-4"/>
                Sao chép
            </button>
        </div>
    </header>

    <section class="shrink-0 border-b border-slate-200 px-6 py-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.5fr_1fr]">
            <div class="rounded-xl border border-blue-100 bg-blue-50/50 px-5 py-4">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex size-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <x-heroicon-o-list-bullet class="size-4"/>
                    </div>
                    <span class="text-sm font-semibold text-slate-800">Tiến trình xử lý đơn hàng</span>
                </div>

                <div class="relative">
                    <div class="absolute left-[16.6667%] right-[16.6667%] top-4 h-0.5 bg-slate-200"></div>
                    <div class="absolute left-[16.6667%] top-4 h-0.5 {{ $currentStatus['progress_class'] ?? 'bg-slate-400' }}" style="width: {{ $progressWidth }}"></div>
                    <div class="relative grid grid-cols-3">
                        @foreach ($statusFlow as $statusKey => $status)
                            @php
                                $step = $loop->iteration;
                                $isDone = $statusStep > $step;
                                $isCurrent = $statusStep === $step;
                                $isReached = $isDone || $isCurrent;
                            @endphp
                            <div class="flex flex-col items-center">
                                <div @class([
                                    'flex size-8 items-center justify-center rounded-full ring-4 ring-white',
                                    ($status['progress_class'] ?? 'bg-slate-400').' text-white' => $isReached,
                                    'bg-white text-slate-400 ring-slate-100' => ! $isReached,
                                ])>
                                    @if ($isReached)
                                        <x-heroicon-s-check class="size-4"/>
                                    @else
                                        <span class="size-2 rounded-full bg-current"></span>
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-semibold {{ $isReached ? ($status['label_classes'] ?? 'text-slate-800') : 'text-slate-400' }}">{{ $status['label'] }}</span>
                                <span class="mt-0.5 text-[11px] text-slate-400">
                                    {{ $isCurrent ? 'Đang thực hiện' : ($isDone ? 'Đã hoàn thành' : 'Chưa thực hiện') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-lime-400 bg-emerald-50/50 px-5 py-4">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex size-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                        <x-heroicon-o-clipboard-document-check class="size-4"/>
                    </div>
                    <span class="text-sm font-semibold text-slate-800">Giao hàng và thanh toán</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-full shadow-sm ring-1',
                            'bg-blue-600 text-white ring-blue-600' => $order->is_delivered,
                            'bg-white text-slate-400 ring-slate-200' => ! $order->is_delivered,
                        ])>
                            <x-heroicon-o-truck class="size-5"/>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Giao hàng</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $order->is_delivered ? 'Đã giao' : 'Chưa giao' }}</p>
                        </div>
                    </div>
                    <div class="flex min-w-0 items-center gap-3">
                        <div @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-full shadow-sm ring-1',
                            'bg-blue-600 text-white ring-blue-600' => $order->is_paid,
                            'bg-white text-slate-400 ring-slate-200' => ! $order->is_paid,
                        ])>
                            <x-heroicon-o-credit-card class="size-5"/>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Thanh toán</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $order->is_paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Điều hướng chỉ đổi nội dung trong vùng tab, không đóng hoặc tải lại modal. --}}
    <nav class="flex shrink-0 items-center gap-7 overflow-x-auto border-b border-slate-200 px-6" aria-label="Chi tiết đơn hàng">
        @foreach ($tabs as $tabKey => $tab)
            <button
                type="button"
                @click="activeTab = '{{ $tabKey }}'"
                :aria-selected="activeTab === '{{ $tabKey }}'"
                :class="activeTab === '{{ $tabKey }}' ? 'border-blue-600 font-semibold text-blue-600' : 'border-transparent font-medium text-slate-500 hover:text-slate-800'"
                class="shrink-0 border-b-2 px-1 py-3 text-sm transition"
                role="tab"
            >
                {{ $tab['label'] }}
                @isset($tab['count'])
                    <span class="ml-1 text-xs text-slate-400">({{ $tab['count'] }})</span>
                @endisset
            </button>
        @endforeach
    </nav>

    <main class="min-h-0 flex-1 overflow-y-auto bg-slate-50/60 p-5">
        @foreach (array_keys($tabs) as $tabKey)
            <div x-show="activeTab === '{{ $tabKey }}'" x-cloak role="tabpanel">
                @include('filament.resources.orders.actions.tabs.'.$tabKey)
            </div>
        @endforeach
    </main>
</div>
