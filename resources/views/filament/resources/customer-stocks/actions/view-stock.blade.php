{{-- Wrapper giữ Filament action độc lập với Livewire component xử lý nghiệp vụ xuất kho. --}}
<livewire:customer-stocks.release-customer-stock :customer-stock-id="$customerStock->id" :key="'view-customer-stock-'.$customerStock->id" />
