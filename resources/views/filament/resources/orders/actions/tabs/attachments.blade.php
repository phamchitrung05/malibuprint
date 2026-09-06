{{-- Tab chi tiết tái sử dụng cùng component với action bảng để không nhân đôi nghiệp vụ file. --}}
<livewire:manage-attachments
    :attachable-type="\App\Models\Order::class"
    :attachable-id="$order->id"
    :key="'view-order-attachments-'.$order->id"
/>
