{{-- Action chỉ làm nhiệm vụ mở modal; component con sở hữu form và nghiệp vụ cập nhật trạng thái. --}}
<livewire:orders.update-order-status :order-id="$order->id" :key="'update-order-status-'.$order->id"/>
