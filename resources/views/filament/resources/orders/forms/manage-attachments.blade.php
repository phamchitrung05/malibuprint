@if ($order?->exists)
    {{-- Trang Edit dùng component này để hiển thị đúng file từ ManagedFile và trạng thái upload hiện tại. --}}
    <livewire:orders.manage-attachments
        :attachable-type="\App\Models\Order::class"
        :attachable-id="$order->getKey()"
        :key="'edit-order-attachments-'.$order->getKey()"
    />
@endif
