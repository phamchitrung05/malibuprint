<x-filament-panels::page>
    {{-- Page cha giữ Filament Table ổn định; panel chi tiết là Livewire component độc lập. --}}
    <div
        class="order-status-demo h-[calc(100dvh-230px)] overflow-hidden [font-size:115%] [&_button]:text-[0.9em]">
        {{-- Cột danh sách được mở rộng để bảng Order hiển thị thông thoáng hơn cột chi tiết. --}}
        <div class="grid h-full min-h-0 gap-4 xl:grid-cols-[minmax(600px,0.9fr)_minmax(620px,1fr)]">

            <div class="min-h-0 flex-1 overflow-y-auto [&_.fi-ta-header]:border-0 [&_.fi-ta-content]:border-0">
                {{ $this->table }}
            </div>


            <livewire:order-detail-panel :order-id="$selectedOrderId"/>
        </div>
    </div>
</x-filament-panels::page>
