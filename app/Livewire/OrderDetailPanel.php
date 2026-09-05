<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderDetailPanel extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    #[Locked]
    public int $orderId;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function updateStatus(string $status): void
    {
        abort_unless(in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true), 422);

        $this->order()->update(['status' => $status]);
    }

    public function cancelOrder(): void
    {
        $this->updateStatus('cancelled');
    }

    #[On('order-cancel')]
    public function cancelFromModal(): void
    {
        $this->cancelOrder();
    }

    #[On('order-complete')]
    public function completeFromModal(): void
    {
        $this->updateStatus('completed');
    }

    public function content(Schema $schema): Schema
    {
        $order = $this->order();

        return $schema->components([
            Tabs::make('order-detail-tabs')
                ->contained(false)
                ->tabs([
                    Tab::make('Thông tin')->schema([
                        SchemaView::make('filament.resources.orders.actions.tabs.info')->viewData(compact('order')),
                    ]),
                    Tab::make('Sản phẩm')->schema([
                        SchemaView::make('filament.resources.orders.actions.tabs.products')->viewData(compact('order')),
                    ]),
                    Tab::make('Thiết kế')->schema([
                        SchemaView::make('filament.resources.orders.actions.tabs.design')->viewData(compact('order')),
                    ]),
                    Tab::make('Lịch sử')->schema([
                        SchemaView::make('filament.resources.orders.actions.tabs.history')->viewData(compact('order')),
                    ]),
                ]),
        ]);
    }

    public function render(): View
    {
        return view('filament.resources.orders.actions.order-detail-panel', [
            'order' => $this->order(),
            'orderStatuses' => config('order.statuses'),
        ]);
    }

    private function order(): Order
    {
        return Order::query()
            ->with(['customer', 'items.productSku.product'])
            ->findOrFail($this->orderId);
    }
}
