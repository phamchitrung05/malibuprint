<?php

namespace App\Livewire;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderCodeService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderDetailPanel extends Component
{
    /** @var list<string> */
    private const ORDER_RELATIONS = ['customer', 'items.productSku.product'];

    private const FALLBACK_NOTE = "Màu sắc theo file thiết kế đã gửi\nCán mờ 2 mặt\nGiao hàng trước thứ 7";

    // Khóa thuộc tính để trình duyệt không thể tự sửa ID ngoài các method của component.
    #[Locked]
    public ?int $orderId = null;

    public bool $isDelivered = false;

    public bool $isPaid = false;

    public function mount(?int $orderId = null): void
    {
        // Nhận Order mặc định từ Page cha trong lần tải trang đầu tiên.
        $this->orderId = $orderId;
        $this->syncOrderFlags();
    }

    /**
     * Chỉ component chi tiết nhận sự kiện và render lại khi người dùng chọn dòng bên trái.
     */
    #[On('order-selected')]
    public function selectOrder(int $orderId): void
    {
        $this->orderId = $orderId;
        $this->syncOrderFlags();
    }

    /**
     * Lưu trạng thái giao hàng ngay khi nhân viên tích hoặc bỏ tích checkbox.
     */
    public function updatedIsDelivered(bool $value): void
    {
        $this->updateOrderFlag('is_delivered', $value);
    }

    /**
     * Lưu trạng thái thanh toán ngay khi chủ cửa hàng tích hoặc bỏ tích checkbox.
     */
    public function updatedIsPaid(bool $value): void
    {
        $this->updateOrderFlag('is_paid', $value);
    }

    /**
     * Nhân bản đơn đang xem và toàn bộ dòng sản phẩm, sau đó chuyển panel sang đơn mới.
     */
    public function duplicateSelectedOrder(): void
    {
        $sourceOrder = $this->getSelectedOrder();

        if (! $sourceOrder) {
            Notification::make()
                ->title('Không tìm thấy đơn hàng để tạo lại')
                ->warning()
                ->send();

            return;
        }

        // Transaction đảm bảo mã sequence, Order và OrderItem luôn được tạo đồng bộ.
        $newOrder = DB::transaction(fn (): Order => $this->duplicateOrder($sourceOrder));
        $this->orderId = $newOrder->id;
        $this->dispatch('order-list-refresh');

        Notification::make()
            ->title('Đã tạo lại đơn hàng')
            ->body('Mã đơn mới: #'.$newOrder->order_code)
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view(
            'filament.resources.orders.pages.order-manager-status',
            $this->buildOrderViewData($this->getSelectedOrder()),
        );
    }

    /**
     * Trả về đúng Order đang chọn và chỉ nạp các quan hệ mà panel phải sử dụng.
     */
    private function getSelectedOrder(): ?Order
    {
        return Order::query()
            ->with(self::ORDER_RELATIONS)
            ->find($this->orderId);
    }

    /**
     * Đồng bộ hai checkbox với Order hiện tại sau khi panel nhận Order mới.
     */
    private function syncOrderFlags(): void
    {
        $order = $this->getSelectedOrder();
        $this->isDelivered = (bool) ($order?->is_delivered ?? false);
        $this->isPaid = (bool) ($order?->is_paid ?? false);
    }

    /**
     * Cập nhật một cờ trạng thái mà không ép giao hàng và thanh toán theo cùng thứ tự.
     */
    private function updateOrderFlag(string $column, bool $value): void
    {
        $order = $this->getSelectedOrder();

        if ($order) {
            $order->update([$column => $value]);
        }
    }

    /**
     * Tạo bản sao Order ở trạng thái chờ xử lý và sao chép từng OrderItem liên quan.
     */
    private function duplicateOrder(Order $sourceOrder): Order
    {
        $newOrder = $sourceOrder->replicate();
        // Đơn tạo lại dùng chung sequence để mã luôn đúng quy luật ORD-DDMMYY-XXX.
        $newOrder->order_code = app(OrderCodeService::class)->generate(now());
        $newOrder->order_date = now();
        $newOrder->status = 'pending';
        $newOrder->is_delivered = false;
        $newOrder->is_paid = false;
        $newOrder->save();

        foreach ($sourceOrder->items as $item) {
            $newOrder->items()->create([
                'product_sku_id' => $item->product_sku_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }

        return $newOrder;
    }

    /**
     * Chuẩn hóa dữ liệu trình bày để Blade chỉ chịu trách nhiệm tạo giao diện.
     *
     * @return array<string, mixed>
     */
    private function buildOrderViewData(?Order $order): array
    {
        $progressStep = $this->getProgressStep($order?->status);

        return [
            'createOrderUrl' => OrderResource::getUrl('create'),
            'detailCode' => $order ? '#'.$order->order_code : '#IN001242',
            'detailCreatedAt' => $order?->order_date?->format('d/m/Y H:i') ?? '03/09/2026 14:20',
            'detailCustomerName' => $order?->customer?->name ?? 'Nguyễn Văn B',
            'detailCustomerPhone' => $order?->customer?->phone ?? '0901 234 567',
            'detailCustomerAddress' => $order?->customer?->address ?? '123 Lê Lợi, TP. Quy Nhơn',
            'detailSubtotal' => $this->formatMoney($order?->subtotal, '420.000đ'),
            'detailDiscount' => $this->formatMoney($order?->discount, '0đ'),
            'detailTotal' => $this->formatMoney($order?->total_amount, '420.000đ'),
            'detailNote' => filled($order?->note) ? $order->note : self::FALLBACK_NOTE,
            'detailStatusLabel' => $this->getStatusLabel($order?->status),
            'detailStatusClasses' => $this->getStatusBadgeClasses($order?->status),
            'customerInitials' => $this->getCustomerInitials($order?->customer?->name),
            'productRows' => $this->buildProductRows($order),
            'progressStep' => $progressStep,
            'progressWidth' => $this->getProgressWidth($progressStep),
        ];
    }

    /**
     * Chuyển OrderItem thành dữ liệu đã format để bảng sản phẩm chỉ việc render.
     *
     * @return array<int, array<string, string>>
     */
    private function buildProductRows(?Order $order): array
    {
        return $order?->items->map(fn ($item): array => [
            'name' => $item->productSku?->product?->name ?? 'Sản phẩm',
            'sku' => $item->productSku?->sku_code ?? 'Đang cập nhật',
            'quantity' => number_format((float) $item->quantity, 0, ',', '.'),
            'unitPrice' => $this->formatMoney($item->unit_price),
            'subtotal' => $this->formatMoney($item->subtotal),
        ])->all() ?? [];
    }

    /**
     * Chuyển mã trạng thái trong database thành nhãn tiếng Việt trên giao diện.
     */
    private function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'processing' => 'Đang in',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => 'Chờ xử lý',
        };
    }

    /**
     * Trả về màu Tailwind phù hợp cho badge trạng thái ở đầu panel.
     */
    private function getStatusBadgeClasses(?string $status): string
    {
        return match ($status) {
            'processing' => 'bg-amber-100 text-amber-600',
            'completed' => 'bg-emerald-100 text-emerald-600',
            'cancelled' => 'bg-red-100 text-red-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * Quy đổi trạng thái database thành vị trí trong quy trình năm bước.
     */
    private function getProgressStep(?string $status): int
    {
        return match ($status) {
            'processing' => 2,
            'completed' => 3,
            default => 1,
        };
    }

    /**
     * Tính phần trăm chiều rộng của đường tiến độ từ bước hiện tại.
     */
    private function getProgressWidth(int $step): float
    {
        return [1 => 0, 2 => 33.5, 3 => 67][$step];
    }

    /**
     * Format tiền theo cách hiển thị Việt Nam và dùng fallback khi chưa có giá trị.
     */
    private function formatMoney(int|float|string|null $amount, string $fallback = '0đ'): string
    {
        return $amount === null ? $fallback : number_format((float) $amount, 0, ',', '.').'đ';
    }

    /**
     * Tạo hai ký tự đại diện từ từ đầu và từ cuối trong tên khách hàng.
     */
    private function getCustomerInitials(?string $name): string
    {
        if (blank($name)) {
            return 'NB';
        }

        $words = preg_split('/\s+/u', trim($name)) ?: [];

        return mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr(end($words) ?: '', 0, 1));
    }
}
