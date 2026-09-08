{{--
    View này là nội dung của Filament table action xem tồn kho một Product.
    ProductInventory chuẩn bị sẵn Product, SKU, movement và allocation; view chỉ
    chịu trách nhiệm trình bày dữ liệu và điều khiển trạng thái mở rộng bằng Alpine.
    Hai modal chi tiết được tách riêng trong thư mục actions/modal để dễ bảo trì.
--}}
@php
    $skus = $product->skus;
    $availableStock = $skus->sum('stock');
    $allocatedStock = $skus->sum(fn ($sku): int => (int) ($sku->allocated_stock ?? 0));
    $totalStock = $availableStock + $allocatedStock;
    $lowStockThreshold = (int) config('status-app.product_sku.low_stock_threshold', 10);
    $lowStockCount = $skus->filter(
        fn ($sku): bool => $sku->stock > 0 && $sku->stock <= $lowStockThreshold,
    )->count();
    $productType = match ($product->product_type) {
        'in_ly' => 'In ly nhựa',
        'in_card' => 'In ấn văn phòng',
        'in_menu' => 'In menu',
        'in_hop' => 'In hộp',
        'in_banner' => 'In banner',
        default => $product->product_type,
    };
@endphp

{{-- Modal content --}}
<div
        x-data="{ receiveSku: null, activeSku: null, modal: null }"
    class="flex h-full min-h-0 flex-col overflow-hidden"
>

    {{-- ================= SCROLL CONTENT ================= --}}
    <div class="order-view-modal min-h-0 flex-1 overflow-y-auto">

        <div class="space-y-5 p-5 lg:p-7">

            {{-- ================= TOP INFO ================= --}}
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.35fr_1.45fr]">

                {{-- PRODUCT INFORMATION --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                    <div class="flex flex-col gap-4 sm:flex-row">

                        {{-- Image --}}
                        <div class="h-36 w-full shrink-0 overflow-hidden rounded-xl
                                            border border-slate-200 bg-slate-100 sm:w-32">
                            <img
                                src="https://placehold.co/300x300?text=Product"
                                alt="{{ $product->name }}"
                                class="size-full object-cover"
                            >
                        </div>

                        {{-- Info --}}
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex items-center gap-2">

                                <div class="flex size-9 items-center justify-center
                                                    rounded-lg bg-blue-50 text-blue-600">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                              d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                                    </svg>
                                </div>

                                <h3 class="font-bold text-slate-900">
                                    Thông tin sản phẩm
                                </h3>
                            </div>


                            <div class="divide-y divide-slate-100 text-sm">

                                <div class="grid grid-cols-[110px_1fr] py-2">
                                    <span class="text-slate-500">Sản phẩm:</span>

                                    <span class="truncate font-semibold text-slate-800">
                                                {{ $product->name }}
                                            </span>
                                </div>

                                <div class="grid grid-cols-[110px_1fr] py-2">
                                    <span class="text-slate-500">Mã SP:</span>

                                    <span class="font-medium text-slate-800">
                                                #{{ $product->id }}
                                            </span>
                                </div>

                                <div class="grid grid-cols-[110px_1fr] py-2">
                                    <span class="text-slate-500">Danh mục:</span>

                                    <span class="font-medium text-slate-800">
                                                {{ $productType }}
                                            </span>
                                </div>

                                <div class="grid grid-cols-[110px_1fr] items-center py-2">
                                    <span class="text-slate-500">Trạng thái:</span>

                                    <div>
                                                <span @class([
                                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                                    'bg-emerald-50 text-emerald-700' => $product->is_active,
                                                    'bg-slate-100 text-slate-600' => ! $product->is_active,
                                                ])>
                                                    <span @class([
                                                        'size-1.5 rounded-full',
                                                        'bg-emerald-500' => $product->is_active,
                                                        'bg-slate-400' => ! $product->is_active,
                                                    ])></span>

                                                     {{ $product->is_active ? 'Đang kinh doanh' : 'Ngừng kinh doanh' }}
                                                </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>


                {{-- ================= STAT CARDS ================= --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    {{-- Tổng SKU --}}
                    <div class="flex items-center gap-4 rounded-2xl border
                                        border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex size-16 shrink-0 items-center justify-center
                                            rounded-2xl bg-blue-50 text-blue-600">

                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                      d="m21 16-9 5-9-5V8l9-5 9 5v8Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                      d="m3.3 7 8.7 5 8.7-5M12 22V12"/>
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Tổng SKU
                            </p>

                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                                {{ $skus->count() }}
                            </p>
                        </div>
                    </div>


                    {{-- Tổng tồn kho --}}
                    <div class="flex items-center gap-4 rounded-2xl border
                                        border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex size-16 shrink-0 items-center justify-center
                                            rounded-2xl bg-emerald-50 text-emerald-600">

                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <ellipse cx="12" cy="5" rx="7" ry="3" stroke-width="1.7"/>
                                <path stroke-width="1.7"
                                      d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/>
                                <path stroke-width="1.7"
                                      d="M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Tổng tồn kho
                            </p>

                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                                {{ number_format($totalStock) }}
                            </p>
                        </div>
                    </div>


                    {{-- Giữ chỗ --}}
                    <div class="flex items-center gap-4 rounded-2xl border
                                        border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex size-16 shrink-0 items-center justify-center
                                            rounded-2xl bg-violet-50 text-violet-600">

                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="1.7"
                                      d="M6 4.5A2.5 2.5 0 0 1 8.5 2h7A2.5 2.5 0 0 1 18 4.5V22l-6-4-6 4V4.5Z"/>
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Đã giữ chỗ
                            </p>

                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                                {{ number_format($allocatedStock) }}
                            </p>
                        </div>
                    </div>


                    {{-- Sắp hết --}}
                    <div class="flex items-center gap-4 rounded-2xl border
                                        border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex size-16 shrink-0 items-center justify-center
                                            rounded-2xl bg-amber-50 text-amber-600">

                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="1.7"
                                      d="M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/>
                                <path stroke-linecap="round" stroke-width="1.7"
                                      d="M12 9v4m0 3h.01"/>
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Sắp hết hàng
                            </p>

                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                                {{ number_format($lowStockCount) }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>


            {{-- ================= FILTER BAR ================= --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">

                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">

                    <div class="grid gap-3 sm:max-w-xl sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-slate-600">Ngày bắt đầu</span>
                            <input wire:model.live="historyStartDate" type="date"
                                   class="fi-input h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-slate-600">Ngày kết thúc</span>
                            <input wire:model.live="historyEndDate" type="date"
                                   class="fi-input h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </label>
                    </div>
                </div>
            </div>


            {{-- ================= TABLE ================= --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">

                <div class="overflow-x-auto">

                    {{-- Mỗi loại tương tác có state riêng để mở modal không làm mất form nhập hàng. --}}
                    <table class="min-w-[900px] w-full text-left">

                        <thead class="bg-slate-50">
                        <tr class="border-b border-slate-200">

                            <th class="px-3 py-3.5 text-xs font-bold uppercase tracking-wide text-slate-500">
                                Mã SKU
                            </th>

                            <th class="px-3 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
                                Tồn kho
                            </th>

                            <th class="px-3 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
                                Giữ chỗ
                            </th>

                            <th class="px-3 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
                                Có thể bán
                            </th>

                            <th class="px-3 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">
                                Giá bán
                            </th>

                            <th class="px-3 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
                                Trạng thái
                            </th>

                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">
                                Thao tác
                            </th>
                        </tr>
                        </thead>


                        <tbody class="divide-y divide-slate-100">

                        @forelse($skus as $sku)

                            @php
                                $available = (int) $sku->stock;
                                $reserved = (int) ($sku->allocated_stock ?? 0);
                                $stock = $available + $reserved;

                                $status = match(true) {
                                    $available <= 0 => 'out',
                                    $available <= $lowStockThreshold => 'low',
                                    default => 'available',
                                };
                            @endphp

                            <tr class="group transition hover:bg-slate-50/80">

                                {{-- SKU --}}
                                <td class="px-3 py-3">
                                                <span class="whitespace-nowrap text-sm font-semibold text-slate-800">
                                                     {{ $sku->sku_code }}
                                                </span>
                                </td>


                                {{-- Stock --}}
                                <td class="px-3 py-3 text-center">
                                                <span class="text-sm font-semibold text-slate-900">
                                                    {{ number_format($stock) }}
                                                </span>
                                </td>


                                {{-- Reserved --}}
                                <td class="px-3 py-3 text-center">
                                                <span class="text-sm text-slate-700">
                                                    {{ number_format($reserved) }}
                                                </span>
                                </td>


                                {{-- Available --}}
                                <td class="px-3 py-3 text-center">
                                                <span class="text-sm font-semibold text-slate-900">
                                                    {{ number_format($available) }}
                                                </span>
                                </td>


                                {{-- Price --}}
                                <td class="px-3 py-3 text-right">
                                                <span class="whitespace-nowrap text-sm font-medium text-slate-900">
                                                     {{ number_format((float) $sku->price, 0, ',', '.') }}đ
                                                </span>
                                </td>


                                {{-- Status --}}
                                <td class="px-3 py-3 text-center">

                                    @if($status === 'available')
                                        <span class="inline-flex items-center gap-1.5
                                                                 whitespace-nowrap rounded-full
                                                                 bg-emerald-50 px-2.5 py-1
                                                                 text-xs font-semibold text-emerald-700">
                                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                        Còn hàng
                                                    </span>

                                    @elseif($status === 'warning')
                                        <span class="inline-flex items-center gap-1.5
                                                                 whitespace-nowrap rounded-full
                                                                 bg-amber-50 px-2.5 py-1
                                                                 text-xs font-semibold text-amber-700">
                                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                        Sắp hết
                                                    </span>

                                    @elseif($status === 'low')
                                        <span class="inline-flex items-center gap-1.5
                                                                 whitespace-nowrap rounded-full
                                                                 bg-red-50 px-2.5 py-1
                                                                 text-xs font-semibold text-red-700">
                                                        <span class="size-1.5 rounded-full bg-red-500"></span>
                                                        Tồn thấp
                                                    </span>

                                    @else
                                        <span class="inline-flex items-center gap-1.5
                                                                 whitespace-nowrap rounded-full
                                                                 bg-slate-100 px-2.5 py-1
                                                                 text-xs font-semibold text-slate-600">
                                                        <span class="size-1.5 rounded-full bg-slate-400"></span>
                                                        Hết hàng
                                                    </span>
                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">

                                        <button
                                            type="button"
                                            title="Nhập hàng"
                                                x-on:click="receiveSku = receiveSku === {{ $sku->id }} ? null : {{ $sku->id }}"
                                            class="flex size-8 items-center justify-center
                                                                rounded-lg border border-slate-200 bg-white
                                                                text-slate-500 transition
                                                               hover:border-blue-200 hover:bg-blue-50
                                                               hover:text-blue-600"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                      d="M12 3v12m0-12-4 4m4-4 4 4M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            title="Xem lịch sử xuất nhập hàng"
                                                x-on:click="receiveSku = null; activeSku = {{ $sku->id }}; modal = 'history'"
                                            class="flex size-8 items-center justify-center
                                                               rounded-lg border border-slate-200 bg-white
                                                               text-slate-500 transition
                                                               hover:border-blue-200 hover:bg-blue-50
                                                               hover:text-blue-600"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                      d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            title="Xem Order đang được cấp hàng"
                                                x-on:click="receiveSku = null; activeSku = {{ $sku->id }}; modal = 'orders'"
                                            class="flex size-8 items-center justify-center
                                                               rounded-lg border border-slate-200 bg-white
                                                               text-slate-500 transition
                                                               hover:border-slate-300 hover:bg-slate-50
                                                               hover:text-slate-800"
                                        >
                                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M4 5h16v14H4z" fill="none" stroke="currentColor"
                                                      stroke-width="1.8"/>
                                                <path d="M8 9h8M8 13h5" fill="none" stroke="currentColor"
                                                      stroke-linecap="round" stroke-width="1.8"/>
                                            </svg>
                                        </button>

                                    </div>
                                </td>

                            </tr>

                            <tr x-show="receiveSku === {{ $sku->id }}" x-cloak class="bg-slate-50/70">
                                <td colspan="7" class="px-4 py-4">
                                    <div x-show="receiveSku === {{ $sku->id }}" x-cloak
                                         class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Số lượng
                                                    nhập</label>
                                                <input wire:model="adjustmentQuantity" type="number" min="1" step="1"
                                                       class="fi-input h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 lg:w-36"
                                                       placeholder="500,1000,...">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <label class="mb-1 block text-xs font-semibold text-slate-600">Lý do
                                                    nhập hàng</label>
                                                <input wire:model="adjustmentReason" type="text" maxlength="500"
                                                       class="fi-input h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                       placeholder="Ví dụ: Nhập bổ sung từ nhà cung cấp">
                                            </div>
                                            <button type="button" wire:click="receiveInventory({{ $sku->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="receiveInventory({{ $sku->id }})"
                                                    class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                                                Xác nhận nhập
                                            </button>
                                        </div>
                                        @error('adjustmentQuantity') <p
                                            class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        @error('adjustmentReason') <p
                                            class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                    Sản phẩm chưa có SKU.
                                </td>
                            </tr>
                        @endforelse

                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal nằm ngoài table để không làm thay đổi display của các dòng bảng. --}}
                @foreach ($skus as $sku)
                    {{-- Modal nằm ngoài table nhưng được chuẩn bị sẵn để icon chỉ đổi trạng thái hiển thị. --}}
                    @include('filament.pages.actions.modal.sku-inventory-history', [
                        'sku' => $sku,
                    ])
                    @include('filament.pages.actions.modal.sku-inventory-orders', [
                        'sku' => $sku,
                    ])
                @endforeach

        </div>
    </div>

</div>
