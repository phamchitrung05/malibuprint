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
    class="release-stock-modal flex min-h-[520px] flex-col overflow-hidden text-slate-900"
>
    {{-- Header của giao diện thay thế hoàn toàn tiêu đề mặc định của Filament modal. --}}
    <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <x-heroicon-o-truck class="size-6" />
            </div>

            <div>
                <h2 class="text-xl leading-tight font-bold tracking-tight text-slate-950">Xuất hàng cho khách</h2>
                <p class="mt-1 text-sm text-slate-500">Xem thông tin khách hàng và danh sách hàng còn tồn.</p>
            </div>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50/60 p-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-blue-50 text-lg font-bold text-blue-600">
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
                        <p class="sm:col-span-2">
                            <span class="text-slate-500">Địa chỉ:</span>
                            <span class="ml-2 font-medium text-slate-900">{{ $customer->address ?: 'Chưa có' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-5 mb-3 flex items-center gap-2">
            <x-heroicon-o-cube class="size-5 text-slate-500" />
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
                    <table class="w-full min-w-[900px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100/90 text-left">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Sản phẩm</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">SKU</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">ĐVT</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Nhập kho</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Đã xuất</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Còn lại</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Xuất lần này</th>
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
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $remainingQuantity }}"
                                            step="1"
                                            wire:model="quantities.{{ $item->id }}"
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
                                    <td colspan="7" class="py-8 text-center text-slate-500">Order này chưa có hàng tồn.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @error('quantities')
                <p class="pt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
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
                    <table class="w-full min-w-[900px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100/90 text-left">
                                <th>Mã phiếu</th>
                                <th>Ngày xuất</th>
                                <th>Sản phẩm</th>
                                <th>SKU</th>
                                <th class="text-right">Số lượng</th>
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
                                    <td>{{ $release->creator?->name ?? 'Hệ thống' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">Chưa có phiếu xuất hàng.</td>
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
                <x-heroicon-o-truck class="size-4" />
                Tạo đợt xuất hàng
            </button>
        </div>
    </div>
</div>
