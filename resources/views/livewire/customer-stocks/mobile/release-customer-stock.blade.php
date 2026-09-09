<div class="flex h-full flex-col bg-white">
    <header class="flex shrink-0 items-start gap-3 border-b border-slate-100 px-4 py-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <x-heroicon-o-truck class="size-6"/>
        </span>
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-bold leading-6 tracking-[-0.3px]">Xuất hàng cho khách</h1>
            <p class="mt-1 text-xs leading-5 text-slate-500">Thông tin khách hàng và hàng còn tồn</p>
        </div>
        <button
            type="button"
            x-on:click="close()"
            aria-label="Đóng modal"
            class="-mr-2 -mt-1 flex size-11 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100"
        >
            <x-heroicon-o-x-mark class="size-6"/>
        </button>
    </header>

    <div class="mobile-order-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <div class="space-y-3 p-4">
            <section aria-labelledby="mobile-customer-name" class="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-600">
                        {{ $initials ?: 'KH' }}
                    </span>
                    <div class="min-w-0">
                        <h2 id="mobile-customer-name" class="break-words text-lg font-bold leading-6">{{ $customerName }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Khách hàng</p>
                    </div>
                </div>

                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-slate-500">Điện thoại:</dt>
                        <dd>
                            @if ($customer->phone)
                                <a href="tel:{{ $customer->phone }}" class="font-medium hover:text-blue-600">{{ $customer->phone }}</a>
                            @else
                                Chưa có
                            @endif
                        </dd>
                    </div>
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-slate-500">Địa chỉ:</dt>
                        <dd class="min-w-0 break-words">{{ $customer->address ?: 'Chưa có' }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-x-2">
                        <dt class="text-slate-500">Mã Order:</dt>
                        <dd class="font-medium">{{ $order->order_code }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-x-2">
                        <dt class="text-slate-500">Phí giao hàng toàn Order:</dt>
                        <dd class="font-medium tabular-nums">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}đ</dd>
                    </div>
                </dl>
            </section>

            <section aria-label="Thống kê tồn kho" class="grid grid-cols-2 gap-2.5">
                <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3">
                    <span class="mb-2 flex size-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <x-heroicon-o-cube class="size-5"/>
                    </span>
                    <p class="text-xs leading-5 text-slate-500">Số lượng tồn</p>
                    <p class="mt-1 text-2xl font-bold leading-7 tracking-tight text-blue-600 tabular-nums">
                        {{ number_format($totalRemainingQuantity, 0, ',', '.') }}
                        <span class="text-base">sản phẩm</span>
                    </p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3">
                    <span class="mb-2 flex size-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <x-heroicon-o-banknotes class="size-5"/>
                    </span>
                    <p class="text-xs leading-5 text-slate-500">Giá trị sản phẩm tồn</p>
                    <p class="mt-1 break-words text-2xl font-bold leading-7 tracking-tight text-blue-600 tabular-nums">
                        {{ number_format($remainingStockValue, 0, ',', '.') }}đ
                    </p>
                </div>
            </section>

            <h2 class="flex items-center gap-2 pt-1 text-base font-semibold">
                <x-heroicon-o-cube class="size-5 text-slate-500"/>
                Thông tin xuất hàng
            </h2>
        </div>

        <nav class="sticky top-0 z-10 border-b border-slate-200 bg-white" aria-label="Thông tin xuất hàng">
            <div class="mobile-order-scroll flex overflow-x-auto px-4" role="tablist" aria-label="Xuất hàng">
                @foreach ([
                    'inventory' => 'Hàng còn tồn',
                    'receipts' => 'Phiếu thu & xác nhận',
                    'history' => 'Lịch sử xuất hàng',
                ] as $tabKey => $tabLabel)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="tab = '{{ $tabKey }}'"
                        x-bind:aria-selected="tab === '{{ $tabKey }}'"
                        x-bind:tabindex="tab === '{{ $tabKey }}' ? 0 : -1"
                        x-bind:class="tab === '{{ $tabKey }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>
        </nav>

        <main class="min-h-64 bg-slate-50/40 p-4">
            <section x-show="tab === 'inventory'" x-cloak role="tabpanel" class="space-y-3">
                @forelse ($customerStock->items as $item)
                    @php
                        $sku = $item->orderItem->productSku;
                        $remainingQuantity = $item->remainingQuantity();
                        $selectedQuantity = max(0, (int) ($quantities[$item->id] ?? 0));
                        $serviceUnitPrice = $item->orderItem->services->sum(
                            fn ($service): float => (float) $service->unit_price,
                        );
                    @endphp
                    <article wire:key="mobile-release-stock-item-{{ $item->id }}" class="rounded-xl border border-blue-200 bg-white p-3.5">
                        <h3 class="break-words text-lg font-semibold leading-6">{{ $sku->product->name }}</h3>
                        <p class="mt-1 break-all text-xs text-slate-500">
                            SKU: {{ $sku->sku_code }} <span class="mx-1">·</span> ĐVT: {{ $sku->product->unit }}
                        </p>

                        <dl class="my-3 grid grid-cols-3 divide-x divide-blue-100 text-center">
                            <div>
                                <dt class="text-xs text-slate-500">Nhập kho</dt>
                                <dd class="mt-1 text-base font-medium tabular-nums">{{ number_format($item->received_quantity, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Đã xuất</dt>
                                <dd class="mt-1 text-base font-medium tabular-nums">{{ number_format($item->released_quantity, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Còn lại</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums">{{ number_format($remainingQuantity, 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 rounded-lg bg-blue-50/80 p-2.5">
                            <div class="min-w-0 space-y-1">
                                @forelse ($item->orderItem->services as $service)
                                    <div>
                                        <p class="break-words font-medium text-blue-600">{{ $service->service_name }}</p>
                                        <p class="text-xs text-slate-500">{{ number_format((float) $service->unit_price, 0, ',', '.') }}đ/SP</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Dịch vụ: Không có</p>
                                @endforelse
                            </div>
                            <div class="border-l border-blue-200 pl-3 text-right">
                                <p class="text-xs text-slate-500">Phí DV còn lại</p>
                                <p class="font-medium text-blue-600 tabular-nums">
                                    {{ number_format($remainingQuantity * $serviceUnitPrice, 0, ',', '.') }}đ
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <label for="mobile-release-quantity-{{ $item->id }}" class="text-sm text-slate-500">Xuất lần này</label>
                            <div @class([
                                'flex h-10 overflow-hidden rounded-lg border bg-slate-50',
                                'border-slate-100 text-slate-400' => $remainingQuantity === 0,
                                'border-slate-200 text-slate-700' => $remainingQuantity > 0,
                            ])>
                                <button
                                    type="button"
                                    wire:click="$set('quantities.{{ $item->id }}', {{ max(0, $selectedQuantity - 1) }})"
                                    @disabled($remainingQuantity === 0 || $selectedQuantity === 0)
                                    aria-label="Giảm số lượng {{ $sku->product->name }}"
                                    class="flex w-10 items-center justify-center bg-slate-100 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <x-heroicon-o-minus class="size-4"/>
                                </button>
                                <input
                                    id="mobile-release-quantity-{{ $item->id }}"
                                    type="number"
                                    inputmode="numeric"
                                    min="0"
                                    max="{{ $remainingQuantity }}"
                                    step="1"
                                    wire:model.live.debounce.300ms="quantities.{{ $item->id }}"
                                    @disabled($remainingQuantity === 0)
                                    class="release-quantity-input w-14 border-0 bg-transparent p-0 text-center text-sm tabular-nums outline-none disabled:cursor-not-allowed"
                                >
                                <button
                                    type="button"
                                    wire:click="$set('quantities.{{ $item->id }}', {{ min($remainingQuantity, $selectedQuantity + 1) }})"
                                    @disabled($remainingQuantity === 0 || $selectedQuantity >= $remainingQuantity)
                                    aria-label="Tăng số lượng {{ $sku->product->name }}"
                                    class="flex w-10 items-center justify-center bg-slate-100 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <x-heroicon-o-plus class="size-4"/>
                                </button>
                            </div>
                        </div>
                        @error("quantities.{$item->id}")
                            <p class="pt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                        Order này chưa có hàng tồn.
                    </p>
                @endforelse

                @error('quantities')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <dl class="space-y-2 rounded-xl border border-blue-200 bg-blue-50/60 p-3.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tiền sản phẩm dự kiến</dt>
                        <dd class="shrink-0 font-medium tabular-nums">{{ number_format($releaseProductPreview, 0, ',', '.') }}đ</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tiền dịch vụ dự kiến</dt>
                        <dd class="shrink-0 font-medium text-blue-600 tabular-nums">{{ number_format($releaseServicePreview, 0, ',', '.') }}đ</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-blue-200 pt-2.5 text-base font-semibold">
                        <dt>Tạm tính trước phân bổ</dt>
                        <dd class="shrink-0 tabular-nums">{{ number_format($releaseProductPreview + $releaseServicePreview, 0, ',', '.') }}đ</dd>
                    </div>
                </dl>
            </section>

            <section x-show="tab === 'receipts'" x-cloak role="tabpanel">
                <livewire:customer-stocks.customer-stock-release-history
                    :customer-stock-id="$customerStock->id"
                    :key="'mobile-customer-stock-receipts-'.$customerStock->id"
                />
            </section>

            <section x-show="tab === 'history'" x-cloak role="tabpanel" class="space-y-3">
                @forelse ($customerStock->releases as $release)
                    <article wire:key="mobile-release-history-{{ $release->id }}" class="rounded-xl border border-slate-200 bg-white p-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $release->release_code }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ $release->released_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <strong class="text-base text-blue-600 tabular-nums">{{ number_format((float) $release->total_amount, 0, ',', '.') }}đ</strong>
                        </div>

                        <div class="mt-3 space-y-2 border-y border-slate-100 py-3">
                            @foreach ($release->items as $releaseItem)
                                <div class="flex items-start justify-between gap-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="break-words font-medium">{{ $releaseItem->customerStockItem->orderItem->productSku->product->name }}</p>
                                        <p class="mt-0.5 break-all text-xs text-slate-500">{{ $releaseItem->customerStockItem->orderItem->productSku->sku_code }}</p>
                                    </div>
                                    <span class="shrink-0 tabular-nums">SL: {{ number_format($releaseItem->quantity, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Tiền sản phẩm</dt><dd class="tabular-nums">{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Tiền dịch vụ</dt><dd class="text-blue-600 tabular-nums">{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Giảm giá</dt><dd class="text-red-600 tabular-nums">-{{ number_format((float) $release->allocated_discount, 0, ',', '.') }}đ</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Điều chỉnh</dt><dd class="tabular-nums">{{ (float) $release->reconciliation_adjustment > 0 ? '+' : '' }}{{ number_format((float) $release->reconciliation_adjustment, 0, ',', '.') }}đ</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phí giao hàng</dt><dd class="tabular-nums">{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}đ</dd></div>
                            <div class="flex justify-between gap-3 border-t border-slate-100 pt-2"><dt class="text-slate-500">Người xuất</dt><dd class="text-right">{{ $release->creator?->name ?? 'Hệ thống' }}</dd></div>
                        </dl>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                        Chưa có phiếu xuất hàng.
                    </p>
                @endforelse
            </section>
        </main>
    </div>

    <footer x-show="tab === 'inventory'" class="shrink-0 border-t border-slate-200 bg-white px-4 py-3">
        @unless ($hasRemainingStock)
            <p class="mb-2 text-center text-xs text-slate-500">Đã xuất hết hàng trong đơn</p>
        @endunless
        <button
            type="button"
            wire:click="release"
            wire:loading.attr="disabled"
            wire:target="release"
            @disabled(! $hasRemainingStock)
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-base font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
        >
            <x-heroicon-o-truck class="size-5"/>
            <span wire:loading.remove wire:target="release">Tạo đợt xuất hàng</span>
            <span wire:loading wire:target="release">Đang tạo phiếu...</span>
        </button>
    </footer>
</div>
