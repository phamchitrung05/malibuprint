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
@endphp

<div
    x-data="{ tab: 'inventory' }"
    class="release-stock-modal flex-col overflow-hidden text-slate-900 flex h-full min-h-0"
>
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
                            <span class="ml-2 font-medium text-slate-900">{{ $customer->address ?: 'Chưa có' }}</span>
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
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                        <x-heroicon-o-wrench-screwdriver class="size-8"/>
                    </div>
                    <div>
                        <p class="text-[16px] text-slate-500">Tổng tiền dịch vụ</p>
                        <div class="mt-1 whitespace-nowrap text-[29px] font-bold leading-none tracking-tight text-violet-700">
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
                <button
                    type="button"
                    x-on:click="tab = 'inventory'"
                    x-bind:class="tab === 'inventory' ? 'text-blue-600 active-tab' : 'text-slate-500'"
                    class="relative shrink-0 px-1 py-3 text-sm font-semibold transition"
                >
                    Danh sách hàng còn tồn
                </button>
                <button
                    type="button"
                    x-on:click="tab = 'receipts'"
                    x-bind:class="tab === 'receipts' ? 'text-blue-600 active-tab' : 'text-slate-500'"
                    class="relative shrink-0 px-1 py-3 text-sm font-semibold transition"
                >
                    Lịch sử phiếu thu và xác nhận
                </button>
                <button
                    type="button"
                    x-on:click="tab = 'history'"
                    x-bind:class="tab === 'history' ? 'text-blue-600 active-tab' : 'text-slate-500'"
                    class="relative shrink-0 px-1 py-3 text-sm font-semibold transition"
                >
                    Lịch sử xuất hàng
                </button>
            </div>
        </div>

        <div x-show="tab === 'inventory'" x-cloak>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] border-collapse">
                        <thead>
                        <tr class="bg-slate-100/90 text-left">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Sản phẩm</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">SKU</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">ĐVT</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Nhập kho
                            </th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Đã xuất</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Còn lại</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Dịch vụ</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Phí DV còn lại</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Xuất lần
                                này
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($customerStock->items as $item)
                            @php
                                $sku = $item->orderItem->productSku;
                                $remainingQuantity = $item->remainingQuantity();
                            @endphp
                            <tr wire:key="release-stock-item-{{ $item->id }}" class="transition hover:bg-slate-50">
                                <td class="font-medium">{{ $sku->product->name }}</td>
                                <td>{{ $sku->sku_code }}</td>
                                <td>{{ $sku->product->unit }}</td>
                                <td class="text-right">{{ number_format($item->received_quantity) }}</td>
                                <td class="text-right">{{ number_format($item->released_quantity) }}</td>
                                <td class="text-right font-medium">{{ number_format($remainingQuantity) }}</td>
                                <td>
                                    @forelse ($item->orderItem->services as $service)
                                        <p class="font-medium text-blue-700">{{ $service->service_name }}</p>
                                        <p class="text-xs text-slate-500">{{ number_format((float) $service->unit_price, 0, ',', '.') }}đ/SP</p>
                                    @empty
                                        <span class="text-slate-400">Không có</span>
                                    @endforelse
                                </td>
                                <td class="text-right font-medium text-blue-700">
                                    {{ number_format($remainingQuantity * $item->orderItem->services->sum(fn ($service): float => (float) $service->unit_price), 0, ',', '.') }}đ
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="{{ $remainingQuantity }}"
                                        step="1"
                                        wire:model.live.debounce.300ms="quantities.{{ $item->id }}"
                                        @disabled($remainingQuantity === 0)
                                        class="release-quantity-input block w-32 rounded-lg bg-slate-50 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                    @error("quantities.{$item->id}")
                                    <p class="pt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-500">Order này chưa có hàng tồn.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @error('quantities')
            <p class="pt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-3 grid gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-slate-500">Tiền sản phẩm dự kiến</p>
                    <p class="mt-1 font-bold text-slate-900">{{ number_format($releaseProductPreview, 0, ',', '.') }}đ</p>
                </div>
                <div>
                    <p class="text-slate-500">Tiền dịch vụ dự kiến</p>
                    <p class="mt-1 font-bold text-blue-700">{{ number_format($releaseServicePreview, 0, ',', '.') }}đ</p>
                </div>
                <div>
                    <p class="text-slate-500">Tạm tính trước phân bổ</p>
                    <p class="mt-1 font-bold text-slate-900">{{ number_format($releaseProductPreview + $releaseServicePreview, 0, ',', '.') }}đ</p>
                </div>
            </div>
        </div>

        <div x-show="tab === 'receipts'" x-cloak>
            <livewire:customer-stock-release-history
                :customer-stock-id="$customerStock->id"
                :key="'customer-stock-receipts-'.$customerStock->id"
            />
        </div>

        <div x-show="tab === 'history'" x-cloak>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1200px] border-collapse">
                        <thead>
                        <tr class="bg-slate-100/90 text-left">
                            <th>Mã phiếu</th>
                            <th>Ngày xuất</th>
                            <th>Sản phẩm</th>
                            <th>SKU</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Tiền SP</th>
                            <th class="text-right">Tiền DV</th>
                            <th class="text-right">Giảm giá</th>
                            <th class="text-right">Điều chỉnh</th>
                            <th class="text-right">Phí giao hàng</th>
                            <th class="text-right">Tổng phiếu</th>
                            <th>Người xuất</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($customerStock->releases as $release)
                            <tr wire:key="release-history-{{ $release->id }}" class="transition hover:bg-slate-50">
                                <td class="font-medium">{{ $release->release_code }}</td>
                                <td>{{ $release->released_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @foreach ($release->items as $releaseItem)
                                        <p>{{ $releaseItem->customerStockItem->orderItem->productSku->product->name }}</p>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach ($release->items as $releaseItem)
                                        <p>{{ $releaseItem->customerStockItem->orderItem->productSku->sku_code }}</p>
                                    @endforeach
                                </td>
                                <td class="text-right">
                                    @foreach ($release->items as $releaseItem)
                                        <p>{{ number_format($releaseItem->quantity) }}</p>
                                    @endforeach
                                </td>
                                <td class="text-right">{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</td>
                                <td class="text-right font-medium text-blue-700">{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</td>
                                <td class="text-right text-red-600">-{{ number_format((float) $release->allocated_discount, 0, ',', '.') }}đ</td>
                                <td class="text-right text-slate-500">
                                    {{ (float) $release->reconciliation_adjustment > 0 ? '+' : '' }}{{ number_format((float) $release->reconciliation_adjustment, 0, ',', '.') }}đ
                                </td>
                                <td class="text-right">{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}
                                    đ
                                </td>
                                <td class="text-right font-medium">{{ number_format((float) $release->total_amount, 0, ',', '.') }}
                                    đ
                                </td>
                                <td>{{ $release->creator?->name ?? 'Hệ thống' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-8 text-center text-slate-500">Chưa có phiếu xuất hàng.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
