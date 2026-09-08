<?php

namespace App\Services;

use App\Enums\FulfillmentMode;
use App\Models\Customer;
use App\Models\Order;
use App\Models\StockRelease;
use App\Support\StatusApp;

class PrintDocumentFactory
{
    /**
     * Chuẩn hóa Order thành dữ liệu độc lập với giao diện để mọi mẫu in dùng chung một cấu trúc.
     *
     * @return array<string, mixed>
     */
    public function forOrder(Order $order): array
    {
        $order->loadMissing([
            'customer',
            'creator',
            'items.productSku.product',
            'items.services',
        ]);

        $fulfillmentMode = $order->fulfillment_mode instanceof FulfillmentMode
            ? $order->fulfillment_mode->value
            : (string) $order->fulfillment_mode;

        return [
            'browser_title' => "Đơn hàng {$order->order_code}",
            'print_button_label' => 'In đơn hàng',
            'document_title' => 'ĐƠN ĐẶT HÀNG',
            'code' => $order->order_code,
            'date_label' => 'Ngày đặt hàng',
            'date' => $order->order_date,
            'secondary_date_label' => 'Ngày giao hàng',
            'secondary_date' => $order->delivery_date,
            'employee' => $order->creator?->name,
            'customer' => $this->customerData($order->customer),
            'info_heading' => 'THÔNG TIN ĐƠN HÀNG',
            'document_type' => StatusApp::label('order.fulfillment_mode', $fulfillmentMode),
            'payment_status' => StatusApp::label('order.payment_summary', $order->is_paid),
            'status' => StatusApp::label('order.status', $order->status),
            'note' => $order->note,
            'items' => $order->items->map(function ($item): array {
                $product = $item->productSku?->product;
                $description = collect([
                    filled($item->productSku?->sku_code) ? "SKU: {$item->productSku->sku_code}" : null,
                    filled($product?->note) ? $product->note : null,
                ])->filter()->values()->all();

                return [
                    'name' => $product?->name ?? 'Sản phẩm không còn tồn tại',
                    'description' => $description,
                    'quantity' => (int) $item->quantity,
                    'unit' => $product?->unit,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->subtotal,
                    'services' => $item->services->map(fn ($service): array => [
                        'name' => $service->service_name,
                        'quantity' => (int) $service->quantity,
                        'unit_price' => (float) $service->unit_price,
                        'total' => (float) $service->subtotal,
                    ])->values()->all(),
                ];
            })->all(),
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'shipping_fee' => (float) $order->shipping_fee,
            'adjustment' => 0,
            'total' => (float) $order->total_amount,
            'total_label' => 'TỔNG THANH TOÁN',
        ];
    }

    /**
     * Phiếu thu dùng snapshot của lần xuất kho, không tính lại từ Order gốc.
     *
     * @return array<string, mixed>
     */
    public function forStockRelease(StockRelease $release): array
    {
        $release->loadMissing([
            'customerStock.customer',
            'customerStock.order',
            'creator',
            'items.customerStockItem.orderItem.productSku.product',
            'items.services',
            'payment.confirmer',
        ]);

        $payment = $release->payment;

        return [
            'browser_title' => "Phiếu thu {$release->release_code}",
            'print_button_label' => 'In phiếu thu',
            'document_title' => 'PHIẾU THU',
            'code' => $release->release_code,
            'date_label' => 'Ngày xuất kho',
            'date' => $release->released_at,
            'secondary_date_label' => 'Ngày thanh toán',
            'secondary_date' => $payment?->payment_date,
            'employee' => $payment?->confirmer?->name ?? $release->creator?->name,
            'customer' => $this->customerData($release->customerStock?->customer),
            'info_heading' => 'THÔNG TIN PHIẾU THU',
            'document_type' => "Phiếu xuất {$release->release_code}",
            'payment_status' => $payment
                ? StatusApp::label('payment.status', $payment->status)
                : 'Chưa thanh toán',
            'status' => $payment ? 'Đã xác nhận thu tiền' : 'Chưa xác nhận',
            'note' => $payment?->note ?? $release->note,
            'items' => $release->items->map(function ($item): array {
                $orderItem = $item->customerStockItem?->orderItem;
                $product = $orderItem?->productSku?->product;
                $description = collect([
                    filled($orderItem?->productSku?->sku_code) ? "SKU: {$orderItem->productSku->sku_code}" : null,
                ])->filter()->values()->all();

                return [
                    'name' => $product?->name ?? 'Sản phẩm không còn tồn tại',
                    'description' => $description,
                    'quantity' => (int) $item->quantity,
                    'unit' => $product?->unit,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->amount,
                    'services' => $item->services->map(fn ($service): array => [
                        'name' => $service->service_name,
                        'quantity' => (int) $service->quantity,
                        'unit_price' => (float) $service->unit_price,
                        'total' => (float) $service->subtotal,
                    ])->values()->all(),
                ];
            })->all(),
            'subtotal' => (float) $release->gross_product_amount + (float) $release->gross_service_amount,
            'discount' => (float) $release->allocated_discount,
            'shipping_fee' => (float) $release->allocated_shipping_fee,
            'adjustment' => (float) $release->reconciliation_adjustment,
            'total' => (float) ($payment?->amount ?? $release->total_amount),
            'total_label' => 'TỔNG ĐÃ THU',
        ];
    }

    /** @return array{name: ?string, address: ?string, phone: ?string, code: ?string} */
    private function customerData(?Customer $customer): array
    {
        return [
            'name' => $customer?->name,
            'address' => $customer?->address,
            'phone' => $customer?->phone,
            'code' => $customer?->uuid,
        ];
    }
}
