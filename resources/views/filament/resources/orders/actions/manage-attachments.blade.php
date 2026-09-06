{{-- Action Filament chỉ mở modal; toàn bộ upload, retry và liên kết nằm trong Livewire component dùng chung. --}}
<livewire:manage-attachments
    :attachable-type="\App\Models\Order::class"
    :attachable-id="$order->id"
    :key="'manage-order-attachments-'.$order->id"
/>
