<x-filament-panels::page>
    {{-- Page cha giữ Filament Table ổn định; panel chi tiết là Livewire component độc lập. --}}
    <div class="order-status-demo h-[calc(100dvh-230px)] overflow-hidden bg-slate-50 text-slate-900 [font-size:115%] [&_.border]:border-slate-200/60 [&_button]:text-[0.9em]">
        {{-- Cột danh sách được mở rộng để bảng Order hiển thị thông thoáng hơn cột chi tiết. --}}
        <div class="grid h-full min-h-0 gap-4 xl:grid-cols-[minmax(600px,0.9fr)_minmax(620px,1fr)]">
            <section class="flex min-h-0 min-w-0 flex-col space-y-4 overflow-hidden">
                {{-- Bộ lọc trạng thái hiện giữ giao diện tĩnh và không tham gia quá trình chọn Order. --}}
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                    <div class="flex min-w-max items-center gap-1 text-base font-semibold">
                        <button class="rounded-xl bg-blue-50 px-5 py-3 text-blue-600 ring-1 ring-blue-300">Tất cả <b class="ml-1 rounded-full bg-blue-100 px-2 py-0.5">128</b></button>
                        <button class="rounded-xl px-4 py-3 text-slate-600">Hôm nay <b class="ml-1 rounded-full bg-slate-100 px-2 py-0.5">12</b></button>
                        <button class="rounded-xl px-4 py-3 text-slate-600">Chờ xử lý <b class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-red-500">18</b></button>
                        <button class="rounded-xl px-4 py-3 text-slate-600">Đang thiết kế <b class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-600">15</b></button>
                        <button class="rounded-xl px-4 py-3 text-slate-600">Hoàn thành <b class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-600">62</b></button>
                        <button class="rounded-xl px-4 py-3 text-slate-600">Đã hủy <b class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-red-500">13</b></button>
                    </div>
                </div>

                {{-- Filament Table không render lại khi chỉ chuyển Order ở panel bên phải. --}}
                <div class="min-h-0 flex-1 overflow-y-auto [&_.fi-ta-header]:border-0 [&_.fi-ta-content]:border-0">
                    {{ $this->table }}
                </div>
            </section>

            <livewire:order-detail-panel :order-id="$selectedOrderId" />
        </div>
    </div>
</x-filament-panels::page>
