<?php

namespace Tests\Unit;

use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatus;
use App\Support\StatusApp;
use Tests\TestCase;

class StatusAppTest extends TestCase
{
    public function test_config_contains_every_persisted_application_status(): void
    {
        $this->assertSame(
            ['pending', 'processing', 'completed', 'cancelled'],
            array_keys(StatusApp::values('order.status')),
        );
        $this->assertSame(
            array_column(FulfillmentMode::cases(), 'value'),
            array_keys(StatusApp::values('order.fulfillment_mode')),
        );
        $this->assertSame(
            array_column(FulfillmentStatus::cases(), 'value'),
            array_keys(StatusApp::values('order.fulfillment_status')),
        );
        $this->assertSame(
            ['pending', 'completed', 'cancelled'],
            array_keys(StatusApp::values('payment.status')),
        );
        $this->assertSame(
            ['pending', 'shipping', 'delivered'],
            array_keys(StatusApp::values('shipping.status')),
        );
        $this->assertSame(
            ['active', 'inactive'],
            array_keys(StatusApp::values('product_sku.status')),
        );
        $this->assertSame(
            ['pending', 'uploading', 'ready', 'failed', 'deleted'],
            array_keys(StatusApp::values('managed_file.status')),
        );
        $this->assertSame(
            ['allocated', 'consumed', 'released'],
            array_keys(StatusApp::values('inventory_allocation.status')),
        );
    }

    public function test_order_transitions_are_read_from_status_app(): void
    {
        $this->assertTrue(StatusApp::canTransition('order.status', 'pending', 'processing'));
        $this->assertTrue(StatusApp::canTransition('order.status', 'processing', 'cancelled'));
        $this->assertFalse(StatusApp::canTransition('order.status', 'completed', 'cancelled'));
    }
}
