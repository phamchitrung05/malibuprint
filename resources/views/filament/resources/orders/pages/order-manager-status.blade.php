{{-- Component con chỉ render panel chi tiết để thao tác chọn dòng phản hồi nhanh hơn. --}}
<section x-data="{ activeTab: 'info' }" class="relative min-h-0 min-w-0 overflow-y-auto rounded-2xl">
                {{-- Lớp loading chỉ phủ panel phải, bảng Order bên trái vẫn giữ nguyên và tiếp tục tương tác. --}}
                <div wire:loading.flex class="absolute inset-0 z-50 hidden items-center justify-center rounded-2xl bg-white/65 backdrop-blur-[1px]">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200/60 bg-white px-4 py-3 font-semibold text-slate-600 shadow-sm">
                        <x-heroicon-o-arrow-path class="h-5 w-5 animate-spin text-blue-600"/>
                        Đang tải đơn hàng...
                    </div>
                </div>
                {{-- Nút tạo đơn dùng route Filament chuẩn và nằm độc lập phía trên panel chi tiết. --}}
                <div class="mb-3 flex justify-end">
                    <a href="{{ $createOrderUrl }}"
                       class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                        <x-heroicon-o-plus class="h-5 w-5"/>
                        Tạo đơn hàng
                    </a>
                </div>
                <div class="bg-white border shadow-sm mb-4 border-slate-200 border-b border-slate-100 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3"><h2 class="text-3xl font-extrabold">
                                    {{ $detailCode }}</h2><span
                                    class="flex items-center gap-2 rounded-xl px-4 py-2 font-bold {{ $detailStatusClasses }}"><x-heroicon-o-clock
                                        class="h-5 w-5"/>{{ $detailStatusLabel }}<x-heroicon-o-chevron-down
                                        class="h-4 w-4"/></span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">Tạo
                                ngày {{ $detailCreatedAt }} •　Cập
                                nhật 2 giờ trước</p>
                        </div>
                        <div class="flex gap-2 text-xl">
                            <button
                                type="button"
                                title="Tạo lại đơn hàng đang xem"
                                wire:click="duplicateSelectedOrder"
                                wire:loading.attr="disabled"
                                wire:target="duplicateSelectedOrder"
                                class="rounded-xl border px-3 py-2 disabled:cursor-wait disabled:opacity-60">
                                <x-heroicon-o-arrow-path wire:loading.class="animate-spin" wire:target="duplicateSelectedOrder" class="h-5 w-5"/>
                            </button>
                            <button class="rounded-xl border px-3 py-2">
                                <x-heroicon-o-share class="h-5 w-5"/>
                            </button>
                            <button class="rounded-xl border px-3 py-2">
                                <x-heroicon-o-ellipsis-horizontal class="h-5 w-5"/>
                            </button>
                        </div>
                    </div>
                    <div
                                class="relative mt-8 grid grid-cols-3 text-center text-xs font-bold text-slate-500 before:absolute before:left-[16.6667%] before:right-[16.6667%] before:top-4 before:h-0.5 before:-translate-y-1/2 before:bg-slate-200">
                        {{-- Thanh nền và phần tiến độ màu xanh nằm phía sau các mốc trạng thái. --}}
                        <span style="width: {{ $progressWidth }}%;"
                                class="absolute left-[16.6667%] top-4 z-0 h-0.5 -translate-y-1/2 bg-emerald-500"></span>
                        <div class="relative z-10">
                            <div
                                class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full {{ $progressStep >= 1 ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <x-heroicon-s-check class="h-5 w-5"/>
                            </div>
                            <b>Đơn mới</b><small class="block font-normal">03/09 14:20</small></div>
                        <div class="relative z-10">
                            <div
                                class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full {{ $progressStep >= 2 ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <x-heroicon-s-check class="h-5 w-5"/>
                            </div>
                            <b>Xử lý</b><small class="block font-normal">03/09 15:10</small></div>
                        <div class="relative z-10 {{ $progressStep >= 3 ? 'text-blue-600' : 'text-slate-400' }}">
                            <div
                                class="mx-auto mb-3 flex h-8 w-8 items-center justify-center rounded-full {{ $progressStep > 3 ? 'bg-emerald-500 text-white ring-0' : ($progressStep === 3 ? 'bg-blue-600 text-white ring-8 ring-blue-50' : 'bg-slate-100 text-slate-400 ring-0') }}">
                                <x-heroicon-s-printer class="h-5 w-5"/>
                            </div>
                            <b>Hoàn thành</b><small class="block font-normal">03/09 16:30</small></div>
                    </div>
                    {{-- Hai checkbox này độc lập với tiến độ sản xuất và có thể cập nhật theo bất kỳ thứ tự nào. --}}
                    <div class="mt-8 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700 transition hover:border-emerald-300">
                            <input type="checkbox" wire:model.live="isDelivered" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Đã giao</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700 transition hover:border-blue-300">
                            <input type="checkbox" wire:model.live="isPaid" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>Đã thanh toán</span>
                        </label>
                    </div>
                </div>
                <div
                    class="bg-white border shadow-sm mb-4 border-slate-200 flex overflow-x-auto border-b border-slate-100 text-lg font-bold">
                    <button @click="activeTab = 'info'"
                            :class="activeTab === 'info' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Thông tin
                    </button>
                    <button @click="activeTab = 'products'"
                            :class="activeTab === 'products' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Sản phẩm
                    </button>
                    <button @click="activeTab = 'design'"
                            :class="activeTab === 'design' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Thiết kế (2)
                    </button>
                    <button @click="activeTab = 'history'"
                            :class="activeTab === 'history' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Lịch sử
                    </button>
                    <button @click="activeTab = 'payment'"
                            :class="activeTab === 'payment' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Thanh toán
                    </button>
                    <button @click="activeTab = 'note'"
                            :class="activeTab === 'note' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-500'"
                            class="px-5 py-5">Ghi chú
                    </button>
                </div>
                {{-- Wrapper duy nhất của vùng nội dung; các tab chỉ thay đổi phần tử con bên trong wrapper này. --}}
                <div id="contentTab">
                    <div x-show="activeTab === 'info'" class="bg-white border shadow-sm mb-4 grid gap-4 lg:grid-cols-2">
                        <div class="p-5">
                            <div class="mb-5 flex items-center justify-between"><h3 class="text-lg font-extrabold">Thông
                                    tin khách hàng</h3>
                                <button class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-bold">Xem chi tiết
                                </button>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-xl font-extrabold text-blue-600">
                                    {{ $customerInitials }}
                                </div>
                                <div><b class="text-lg">{{ $detailCustomerName }}</b>
                                    <p class="text-sm text-slate-500">Công ty XYZ</p></div>
                            </div>
                            <div class="mt-5 space-y-3 text-sm text-slate-600">
                                <p class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="h-5 w-5"/>
                                    {{ $detailCustomerPhone }}
                                </p>
                                <p class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="h-5 w-5"/>
                                    {{ $detailCustomerAddress }}
                                </p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="mb-4 flex items-center justify-between"><h3 class="text-lg font-extrabold">Thông
                                    tin đơn hàng</h3>
                                <button
                                    class="flex items-center gap-1 rounded-lg bg-slate-200 px-3 py-2 text-sm font-bold">
                                    <x-heroicon-o-pencil class="h-4 w-4"/>
                                    Chỉnh sửa
                                </button>
                            </div>
                            <div class="space-y-4 text-sm">
                                <p class="flex justify-between"><span class="text-slate-500">Mã đơn</span><b>{{ $detailCode }}</b></p>
                                <p class="flex justify-between"><span class="text-slate-500">Ngày tạo</span><b>{{ $detailCreatedAt }}</b></p>
                                <p class="flex justify-between"><span class="text-slate-500">Trạng thái</span><b>{{ $detailStatusLabel }}</b></p>
                                <p class="flex justify-between"><span class="text-slate-500">Tạm tính</span><b>{{ $detailSubtotal }}</b></p>
                                <p class="flex justify-between"><span class="text-slate-500">Giảm giá</span><b>{{ $detailDiscount }}</b></p>
                                <p class="flex justify-between"><span class="text-slate-500">Tổng tiền</span><b>{{ $detailTotal }}</b></p>
                            </div>
                        </div>
                    </div>
                    {{-- Các tab còn lại dùng nội dung mẫu vì chưa có nghiệp vụ chi tiết tương ứng. --}}
                    <div x-show="activeTab === 'products'" class="mb-4 rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-extrabold">Sản phẩm trong đơn hàng</h3>
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200/60">
                            <table class="w-full min-w-[620px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">SKU</th><th class="px-4 py-3">SL</th><th class="px-4 py-3">Đơn giá</th><th class="px-4 py-3">Thành tiền</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($productRows as $itemIndex => $item)
                                        <tr><td class="px-4 py-4">{{ $itemIndex + 1 }}</td><td class="px-4 py-4 font-bold">{{ $item['name'] }}</td><td class="px-4 py-4 text-slate-500">{{ $item['sku'] }}</td><td class="px-4 py-4">{{ $item['quantity'] }}</td><td class="px-4 py-4">{{ $item['unitPrice'] }}</td><td class="px-4 py-4 font-bold">{{ $item['subtotal'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có order item, đang hiển thị nội dung mẫu.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div x-show="activeTab === 'design'" class="mb-4 rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-extrabold">File thiết kế (2)</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200/60 p-4">
                                <x-heroicon-o-document class="h-8 w-8 text-blue-600"/>
                                <b class="mt-2 block">card_visit_final.pdf</b><small class="text-slate-500">2.4 MB · Đã
                                    tải lên</small></div>
                            <div class="rounded-xl border border-slate-200/60 p-4">
                                <x-heroicon-o-document class="h-8 w-8 text-blue-600"/>
                                <b class="mt-2 block">card_visit_preview.pdf</b><small class="text-slate-500">1.1 MB ·
                                    Đã tải lên</small></div>
                        </div>
                    </div>
                    <div x-show="activeTab === 'history'" class="mb-4 rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-extrabold">Lịch sử đơn hàng</h3>
                        <div class="mt-4 space-y-3 text-sm"><p class="rounded-xl bg-slate-50 p-4">Đang in<span
                                    class="ml-3 text-slate-500">03/09/2026 16:30</span></p>
                            <p class="rounded-xl bg-slate-50 p-4">Đang thiết kế<span class="ml-3 text-slate-500">03/09/2026 15:10</span>
                            </p></div>
                    </div>
                    <div x-show="activeTab === 'payment'" class="mb-4 rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-extrabold">Thanh toán</h3>
                        <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700">
                            <x-heroicon-o-check-circle class="mr-2 inline h-5 w-5"/>
                            Đã thanh toán {{ $detailTotal }}</div>
                    </div>
                    <div x-show="activeTab === 'note'" class="mb-4 rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-extrabold">Ghi chú đơn hàng</h3>
                        <div class="mt-4 whitespace-pre-line rounded-xl bg-amber-50 p-4 text-sm leading-7 text-slate-600">{{ $detailNote }}</div>
                    </div>
                </div>
                <div id="button" class="bg-white border shadow-sm border-slate-300 space-y-4 bg-slate-50/60 p-5">
                    {{-- Card riêng gom ghi chú, tổng tiền và nhóm thao tác cuối đơn hàng. --}}
                    <div class="space-y-4 p-4">
                        <div class="relative grid gap-4 lg:grid-cols-2">
                            {{-- Đường phân cách dọc giúp nhận biết rõ hai khối thông tin song song. --}}
                            <span
                                class="pointer-events-none absolute bottom-0 left-1/2 top-0 hidden w-px -translate-x-1/2 bg-slate-200/60 lg:block"></span>
                            <div class="rounded-2xl border bg-white p-5">
                                <div class="mb-4 flex justify-between"><h3 class="text-lg font-extrabold">Ghi chú
                                        đơn
                                        hàng</h3>
                                    <button class="flex items-center gap-1 font-bold text-slate-600">
                                        <x-heroicon-o-pencil class="h-4 w-4"/>
                                        Chỉnh sửa
                                    </button>
                                </div>
                                <div class="whitespace-pre-line rounded-xl bg-amber-50 p-4 text-sm leading-7 text-slate-600">{{ $detailNote }}</div>
                            </div>
                            <div class="rounded-2xl border bg-white p-5"><h3 class="text-lg font-extrabold">Tổng
                                    tiền</h3>
                                <div class="mt-4 space-y-3 text-sm"><p class="flex justify-between"><span
                                            class="text-slate-500">Tạm tính</span><b>{{ $detailSubtotal }}</b></p>
                                    <p class="flex justify-between"><span
                                            class="text-slate-500">Giảm giá</span><b>{{ $detailDiscount }}</b>
                                    </p>
                                    <p class="flex justify-between"><span
                                            class="text-slate-500">Phí vận chuyển</span><b>0đ</b></p>
                                    <p class="flex justify-between border-t pt-4 text-lg">
                                        <b>Tổng cộng</b>
                                        <b class="text-2xl text-blue-600">{{ $detailTotal }}</b>
                                    </p></div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 rounded-2xl border bg-white p-4">
                            <button class="rounded-xl border px-5 py-3 font-bold">
                                <x-heroicon-o-ellipsis-horizontal class="h-5 w-5"/>
                            </button>
                            <button
                                class="flex-1 flex items-center justify-center gap-2 rounded-xl border px-5 py-3 font-bold text-slate-700">
                                <x-heroicon-o-trash class="h-5 w-5"/>
                                Hủy đơn
                            </button>
                            <button
                                class="flex-[2] flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-bold text-white">
                                Cập nhật trạng thái
                                <x-heroicon-o-chevron-down class="h-5 w-5"/>
                            </button>
                        </div>
                    </div>
                </div>
</section>
