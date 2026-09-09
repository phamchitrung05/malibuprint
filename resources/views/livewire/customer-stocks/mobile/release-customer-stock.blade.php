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
                @foreach ($tabs as $tabKey => $tabConfig)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="tab = '{{ $tabKey }}'"
                        x-bind:aria-selected="tab === '{{ $tabKey }}'"
                        x-bind:tabindex="tab === '{{ $tabKey }}' ? 0 : -1"
                        x-bind:class="tab === '{{ $tabKey }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium"
                    >
                        {{ $tabConfig['mobile_label'] }}
                    </button>
                @endforeach
            </div>
        </nav>

        <main class="min-h-64 bg-slate-50/40 py-3">
            @foreach (array_keys($tabs) as $tabKey)
                <section
                    x-show="tab === '{{ $tabKey }}'"
                    x-cloak
                    role="tabpanel"
                    @class(['space-y-3' => $tabKey !== 'receipts'])
                >
                    @include('livewire.customer-stocks.mobile.tabs.'.$tabKey)
                </section>
            @endforeach
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
