<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Widgets\OrderStatsOverview;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderStatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_displays_order_workflow_counts(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách thống kê',
            'phone' => '0900000088',
        ]);

        $this->createOrders(2, $customer->id, ['status' => 'pending']);
        $this->createOrders(1, $customer->id, ['status' => 'processing']);
        $this->createOrders(2, $customer->id, [
            'status' => 'completed',
            'is_delivered' => false,
            'is_paid' => true,
        ]);
        $this->createOrders(3, $customer->id, [
            'status' => 'completed',
            'is_delivered' => true,
            'is_paid' => false,
            'total_amount' => 100000,
        ]);
        $this->createOrders(1, $customer->id, [
            'status' => 'completed',
            'is_delivered' => false,
            'is_paid' => false,
            'total_amount' => 200000,
        ]);

        Livewire::actingAs($user)
            ->test(OrderStatsOverview::class)
            ->assertSee('Đơn mới tạo')
            ->assertSee('Đơn đang xử lý')
            ->assertSee('Hoàn thành chưa giao')
            ->assertSee('Hoàn thành chưa thanh toán')
            ->assertSeeInOrder(['Đơn mới tạo', '2'])
            ->assertSeeInOrder(['Đơn đang xử lý', '1'])
            ->assertSeeInOrder(['Hoàn thành chưa giao', '3'])
            ->assertSeeInOrder(['Hoàn thành chưa thanh toán', '4'])
            ->assertSee('500.000');
    }

    public function test_order_resource_registers_stats_overview_widget(): void
    {
        $this->assertContains(OrderStatsOverview::class, OrderResource::getWidgets());

        Livewire::actingAs(User::factory()->create())
            ->test(ListOrders::class)
            ->assertSeeLivewire(OrderStatsOverview::class);
    }

    private function createOrders(int $count, int $customerId, array $attributes): void
    {
        foreach (range(1, $count) as $index) {
            Order::query()->create(array_merge([
                'order_code' => uniqid('STATS-', true),
                'customer_id' => $customerId,
                'order_date' => now(),
                'is_delivered' => false,
                'is_paid' => false,
            ], $attributes));
        }
    }
}
