@php
    $customer = $customerStock->customer;
    $order = $customerStock->order;
    $customerName = $customer->name;
    $initials = collect(preg_split('/\s+/', trim($customerName)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $hasRemainingStock = $customerStock->items->contains(
        fn ($item): bool => $item->remainingQuantity() > 0,
    );
    $tabs = [
        'inventory' => [
            'label' => 'Danh sách hàng còn tồn',
            'mobile_label' => 'Hàng còn tồn',
        ],
        'receipts' => [
            'label' => 'Lịch sử phiếu thu và xác nhận',
            'mobile_label' => 'Phiếu thu & xác nhận',
        ],
        'history' => [
            'label' => 'Lịch sử xuất hàng',
            'mobile_label' => 'Lịch sử xuất hàng',
        ],
    ];
@endphp

<div
    x-data="{ tab: 'inventory' }"
    class="release-stock-modal order-view-modal h-[90dvh] overflow-hidden text-slate-900 lg:h-[70vh]"
>
    {{-- Mobile và desktop dùng chung state Livewire quantities và state Alpine tab. --}}
    <div class="h-full lg:hidden">
        @include('livewire.customer-stocks.mobile.release-customer-stock')
    </div>

    <div class="hidden h-full min-h-0 flex-col overflow-hidden lg:flex">
        {{-- Header của giao diện thay thế hoàn toàn tiêu đề mặc định của Filament modal. --}}
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <x-heroicon-o-truck class="size-6"/>
                </div>

                <div>
                    <h2 class="text-xl leading-tight font-bold tracking-tight text-slate-950">Xuất hàng cho khách</h2>
                    <p class="mt-1 text-sm text-slate-500">Xem thông tin khách hàng và danh sách hàng còn tồn.</p>
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50/60 p-5 order-view-modal">
            <section class="rounded-xl t-2 rounded-xl border border-blue-200
                           bg-gradient-to-r from-blue-50/80 via-white to-blue-50/60
                           px-2 py-2">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-16 shrink-0 items-center justify-center rounded-full bg-blue-50 text-lg font-bold text-blue-600">
                        {{ $initials ?: 'KH' }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="mb-3 text-lg font-bold text-slate-950">{{ $customerName }}</h3>

                        <div class="grid gap-2 text-sm sm:grid-cols-2">
                            <p>
                                <span class="text-slate-500">Mã khách hàng:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ $customer->uuid ?? 'Chưa có' }}</span>
                            </p>
                            <p>
                                <span class="text-slate-500">Mã Order:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ $order->order_code }}</span>
                            </p>
                            <p>
                                <span class="text-slate-500">Điện thoại:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ $customer->phone ?: 'Chưa có' }}</span>
                            </p>
                            <p>
                                <span class="text-slate-500">Phí giao hàng toàn Order:</span>
                                <span class="ml-2 font-medium text-slate-900">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}đ</span>
                            </p>
                            <p class="sm:col-span-2">
                                <span class="text-slate-500">Địa chỉ:</span>
                                <span
                                    class="ml-2 font-medium text-slate-900">{{ $customer->address ?: 'Chưa có' }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="mt-5 rounded-xl border border-blue-200
                           bg-gradient-to-r from-blue-50/80 via-white to-blue-50/60
                           px-2 py-2"
            >
                <div
                    class="grid grid-cols-1 gap-5
                               md:grid-cols-[1fr_auto_1fr_auto_1fr]
                               md:items-center"
                >

                    {{-- Stock count --}}
                    <div class="flex items-center gap-5">

                        <div
                            class="flex size-16 shrink-0 items-center justify-center
                                       rounded-full bg-blue-100 text-blue-600"
                        >
                            <svg
                                class="size-9"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="m21 16-9 5-9-5V8l9-5 9 5v8Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="m3.5 7.5 8.5 5 8.5-5M12 12.5V21"
                                />
                            </svg>
                        </div>

                        <div>
                            <p class="text-[16px] text-slate-500">
                                Tổng số lượng tồn
                            </p>

                            <div
                                class="mt-1 text-[29px] font-bold leading-none
                                           tracking-tight text-blue-600"
                            >
                                {{ number_format($totalRemainingQuantity) }} sản phẩm
                            </div>
                        </div>

                    </div>

                    {{-- separator --}}
                    <div class="hidden h-16 w-px bg-blue-200 md:block"></div>

                    {{-- Value --}}
                    <div class="flex items-center gap-5">

                        <div
                            class="flex size-16 shrink-0 items-center justify-center
                                       rounded-full bg-emerald-100 text-emerald-700"
                        >
                            <svg
                                class="size-9"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 3h6l-1.5 4h-3L9 3Zm-1.5 5h9C19 10 21 14 21 17a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4c0-3 2-7 4.5-9Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-width="1.8"
                                    d="M12 11v6m2-5.5c-.5-.5-1.2-.7-2-.7-1.1 0-2 .5-2 1.3 0 1.9 4 .9 4 2.9 0 .8-.9 1.3-2 1.3-.8 0-1.6-.3-2.1-.8"
                                />
                            </svg>
                        </div>

                        <div>
                            <p class="text-[16px] text-slate-500">
                                Giá trị sản phẩm tồn
                            </p>

                            <div
                                class="mt-1 whitespace-nowrap text-[29px]
                                           font-bold leading-none tracking-tight text-blue-600"
                            >
                                {{ number_format($remainingStockValue, 0, ',', '.') }} đ
                            </div>
                        </div>

                    </div>

                    {{-- separator --}}
                    <div class="hidden h-16 w-px bg-blue-200 md:block"></div>

                    {{-- Tổng phí dịch vụ của toàn bộ Order, không giảm theo số lượng đã xuất. --}}
                    <div class="flex items-center gap-5">
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                            <x-heroicon-o-wrench-screwdriver class="size-8"/>
                        </div>
                        <div>
                            <p class="text-[16px] text-slate-500">Tổng tiền dịch vụ</p>
                            <div
                                class="mt-1 whitespace-nowrap text-[29px] font-bold leading-none tracking-tight text-violet-700">
                                {{ number_format($totalServiceValue, 0, ',', '.') }} đ
                            </div>
                        </div>
                    </div>

                </div>
            </section>


            <div class="mt-5 mb-3 flex items-center gap-2">
                <x-heroicon-o-cube class="size-5 text-slate-500"/>
                <h3 class="text-base font-semibold text-slate-950">Thông tin xuất hàng</h3>
            </div>

            <div class="mb-2 border-b border-slate-200">
                <div class="flex items-center gap-7">
                    @foreach ($tabs as $tabKey => $tabConfig)
                        <button
                            type="button"
                            x-on:click="tab = '{{ $tabKey }}'"
                            x-bind:aria-selected="tab === '{{ $tabKey }}'"
                            x-bind:class="tab === '{{ $tabKey }}' ? 'text-blue-600 active-tab' : 'text-slate-500'"
                            class="relative shrink-0 px-1 py-3 text-sm font-semibold transition"
                            role="tab"
                        >
                            {{ $tabConfig['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach (array_keys($tabs) as $tabKey)
                <div x-show="tab === '{{ $tabKey }}'" x-cloak role="tabpanel">
                    @include('livewire.customer-stocks.tabs.'.$tabKey)
                </div>
            @endforeach
        </div>

        <div class="shrink-0 border-t border-slate-200 bg-white px-6 py-3">
            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    wire:click="release"
                    wire:loading.attr="disabled"
                    wire:target="release"
                    x-show="tab === 'inventory'"
                    @disabled(! $hasRemainingStock)
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-heroicon-o-truck class="size-4"/>
                    Tạo đợt xuất hàng
                </button>
            </div>
        </div>
    </div>
</div>
