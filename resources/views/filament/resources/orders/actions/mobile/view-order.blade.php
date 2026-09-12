<div x-data="{ copyFeedback: '' }" class="flex h-full flex-col bg-white">
    <header class="shrink-0 border-b border-slate-100 px-4 py-3">
        <h1 class="text-2xl font-bold leading-7 tracking-[-0.3px]">Chi tiết đơn hàng</h1>
    </header>

    <div class="mobile-order-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <section class="space-y-4 p-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="break-all text-2xl font-bold leading-7 tracking-[-0.4px]">#{{ $order->order_code }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-semibold leading-5 {{ $currentStatus['badge_classes'] }}">
                        <span class="size-1.5 rounded-full bg-current opacity-70"></span>
                        {{ $currentStatus['label'] }}
                    </span>
                </div>
                <div class="mt-2 space-y-1 text-sm flex leading-5 text-slate-500">
                    <p class="flex-auto">Tạo ngày {{ $order->order_date?->format('d/m/Y') ?? '--/--' }}</p>
                    <p class="flex-auto">Cập nhật {{ $order->updated_at?->format('d/m/Y') ?? '--/--' }}</p>
                </div>
            </div>

            <section class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5" aria-label="Tiến trình xử lý">
                <h3 class="flex items-center gap-2 text-base font-semibold leading-[22px]">
                    <span class="rounded-lg bg-blue-100 p-2 text-blue-600"><x-heroicon-o-list-bullet class="size-4"/></span>
                    Tiến trình xử lý
                </h3>
                <div class="relative mt-4">
                    <div class="absolute left-[16.67%] right-[16.67%] top-4 h-0.5 bg-slate-200" aria-hidden="true"></div>
                    <div class="absolute left-[16.67%] top-4 h-0.5 {{ $currentStatus['progress_class'] ?? 'bg-slate-400' }}" style="width: {{ $progressWidth }}" aria-hidden="true"></div>
                    <ol class="relative grid grid-cols-3 gap-1 text-center text-sm leading-5">
                        @foreach ($statusFlow as $status)
                            @php
                                $step = $loop->iteration;
                                $isDone = $statusStep > $step;
                                $isCurrent = $statusStep === $step;
                                $isReached = $isDone || $isCurrent;
                            @endphp
                            <li @if ($isCurrent) aria-current="step" @endif>
                                <span @class([
                                    'mx-auto flex size-8 items-center justify-center rounded-full border ring-4 ring-white',
                                    ($status['progress_class'] ?? 'bg-slate-400').' border-transparent text-white' => $isReached,
                                    'border-slate-200 bg-white text-slate-300' => ! $isReached,
                                ])>
                                    @if ($isReached)
                                        <x-heroicon-s-check class="size-4"/>
                                    @else
                                        <span class="size-2 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <p @class([
                                    'mt-2 font-semibold',
                                    ($status['label_classes'] ?? 'text-slate-800') => $isReached,
                                    'text-slate-400' => ! $isReached,
                                ])>{{ $status['label'] }}</p>
                                <p class="mt-1 text-xs leading-4 text-slate-400">
                                    {{ $isCurrent ? 'Đang thực hiện' : ($isDone ? 'Đã hoàn thành' : 'Chưa thực hiện') }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-2.5">
                <section class="flex min-w-0 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-full',
                        'bg-emerald-600 text-white' => $order->is_delivered,
                        'bg-white text-slate-400 ring-1 ring-slate-200' => ! $order->is_delivered,
                    ])><x-heroicon-o-truck class="size-5"/></span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold leading-5">Giao hàng</h3>
                        <p class="mt-1 truncate text-sm leading-5 text-slate-500">{{ $order->is_delivered ? 'Đã giao' : 'Chưa giao' }}</p>
                    </div>
                </section>
                <section class="flex min-w-0 items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/60 p-3">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-full',
                        'bg-blue-600 text-white' => $order->is_paid,
                        'bg-white text-slate-400 ring-1 ring-slate-200' => ! $order->is_paid,
                    ])><x-heroicon-o-credit-card class="size-5"/></span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold leading-5">Thanh toán</h3>
                        <p class="mt-1 truncate text-sm leading-5 text-slate-500">{{ $order->is_paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}</p>
                    </div>
                </section>
            </div>
        </section>

        <nav class="sticky top-0 z-10 border-b border-slate-200 bg-white" aria-label="Thông tin đơn hàng">
            <div role="tablist" aria-label="Chi tiết" class="mobile-order-scroll flex overflow-x-auto px-4">
                @foreach ($tabs as $tabKey => $tab)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="activeTab = '{{ $tabKey }}'"
                        x-bind:aria-selected="activeTab === '{{ $tabKey }}'"
                        x-bind:tabindex="activeTab === '{{ $tabKey }}' ? 0 : -1"
                        x-bind:class="activeTab === '{{ $tabKey }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-3 py-3.5 text-base font-medium leading-[22px]"
                    >
                        {{ $tab['label'] }}
                        @isset($tab['count'])
                            <span class="ml-1 text-sm leading-5 text-slate-400">({{ $tab['count'] }})</span>
                        @endisset
                    </button>
                @endforeach
            </div>
        </nav>

        <main class="min-h-64 bg-slate-50/60 p-4">
            @foreach (array_keys($tabs) as $tabKey)
                <section x-show="activeTab === '{{ $tabKey }}'" x-cloak role="tabpanel">
                    @if ($tabKey === 'products')
                        <div class="space-y-3">
                            @forelse ($order->items as $item)
                                @php
                                    $serviceTotal = (float) $item->services->sum(fn ($service): float => (float) $service->subtotal);
                                    $itemTotal = (float) $item->subtotal + $serviceTotal;
                                @endphp
                                <article class="rounded-xl border border-slate-200 bg-white p-3.5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
                                            <x-heroicon-o-cube class="size-6"/>
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="break-words text-lg font-semibold leading-6 tracking-[-0.15px]">{{ $item->productSku?->product?->name ?? 'Sản phẩm' }}</h4>
                                            <p class="mt-1 break-all text-sm leading-5 text-slate-500">SKU: {{ $item->productSku?->sku_code ?? '---' }}</p>
                                            <p class="mt-1 text-sm leading-5 text-slate-400">{{ $item->productSku?->product?->note ?? 'Chưa có mô tả' }}</p>
                                        </div>
                                    </div>
                                    <dl class="mt-4 grid grid-cols-2 border-y border-slate-100 py-3">
                                        <div class="border-r border-slate-100">
                                            <dt class="text-sm leading-5 text-slate-500">Số lượng</dt>
                                            <dd class="mt-1 text-lg font-semibold leading-6 tabular-nums">{{ number_format($item->quantity, 0, ',', '.') }}</dd>
                                        </div>
                                        <div class="pl-4">
                                            <dt class="text-sm leading-5 text-slate-500">Đơn giá</dt>
                                            <dd class="mt-1 text-base leading-6 tabular-nums">{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</dd>
                                        </div>
                                    </dl>
                                    <div class="flex items-center justify-between gap-2 py-3 text-base leading-[22px]">
                                        <span class="text-slate-500">Tiền sản phẩm</span>
                                        <strong class="text-lg font-semibold leading-6 tabular-nums">{{ number_format((float) $item->subtotal, 0, ',', '.') }}đ</strong>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse ($item->services as $service)
                                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-blue-50 p-3">
                                                <div class="flex min-w-0 items-center gap-2">
                                                    <x-heroicon-o-printer class="size-5 shrink-0 text-blue-600"/>
                                                    <div class="min-w-0">
                                                        <h5 class="truncate text-base font-medium leading-5 text-blue-600">{{ $service->service_name }}</h5>
                                                        <p class="mt-1 text-sm leading-5 text-slate-500">{{ number_format($service->quantity, 0, ',', '.') }} × {{ number_format((float) $service->unit_price, 0, ',', '.') }}đ</p>
                                                    </div>
                                                </div>
                                                <strong class="ml-auto text-lg font-semibold leading-6 tabular-nums">{{ number_format((float) $service->subtotal, 0, ',', '.') }}đ</strong>
                                            </div>
                                        @empty
                                            <p class="rounded-lg bg-slate-50 p-3 text-sm leading-5 text-slate-400">Dịch vụ: Không có</p>
                                        @endforelse
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                        <span class="text-base font-semibold text-slate-700">Thành tiền</span>
                                        <strong class="text-lg font-bold text-blue-600 tabular-nums">{{ number_format($itemTotal, 0, ',', '.') }}đ</strong>
                                    </div>
                                </article>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Đơn hàng chưa có sản phẩm.</p>
                            @endforelse
                        </div>
                    @elseif ($tabKey === 'attachments')
                        <livewire:orders.manage-attachments
                            :attachable-type="\App\Models\Order::class"
                            :attachable-id="$order->id"
                            :key="'mobile-view-order-attachments-'.$order->id"
                        />
                    @else
                        @include('filament.resources.orders.actions.tabs.'.$tabKey, ['isMobile' => true])
                    @endif
                </section>
            @endforeach
        </main>
    </div>
</div>
