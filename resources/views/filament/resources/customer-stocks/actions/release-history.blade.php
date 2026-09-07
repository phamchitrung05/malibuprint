{{-- Lịch sử và xác nhận Payment được tách khỏi màn hình xuất kho để mỗi modal có một trách nhiệm rõ ràng. --}}
<livewire:customer-stock-release-history :customer-stock-id="$customerStock->id" :key="'customer-stock-release-history-'.$customerStock->id" />
