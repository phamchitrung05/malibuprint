{{-- Filament action mở modal; Livewire component sẽ sở hữu giao diện và nghiệp vụ xuất kho. --}}
<livewire:release-customer-stock :customer-stock-id="$customerStock->id" :key="'release-customer-stock-'.$customerStock->id"/>
