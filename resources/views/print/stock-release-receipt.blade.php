<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu thu {{ 'PT'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        .page { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 18mm; background: white; }
        .header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 16px; }
        .eyebrow { margin: 0 0 6px; color: #475569; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 28px; }
        .code { text-align: right; font-size: 13px; line-height: 1.7; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; margin: 22px 0; font-size: 14px; }
        .meta div { border-bottom: 1px dashed #cbd5e1; padding-bottom: 7px; }
        .meta span { color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f1f5f9; color: #475569; font-size: 11px; letter-spacing: .04em; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #cbd5e1; padding: 9px; }
        .number { text-align: right; white-space: nowrap; }
        .summary { width: 48%; margin: 20px 0 0 auto; font-size: 14px; }
        .summary-row { display: flex; justify-content: space-between; gap: 20px; padding: 7px 0; }
        .summary-row span:first-child { color: #64748b; }
        .summary-total { margin-top: 5px; border-top: 2px solid #0f172a; padding-top: 12px; font-size: 18px; font-weight: 700; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; margin-top: 60px; text-align: center; }
        .signatures p { margin: 0; font-size: 13px; font-weight: 700; }
        .signatures small { display: block; margin-top: 5px; color: #64748b; font-weight: 400; }
        .actions { width: 210mm; margin: 0 auto 20px; text-align: right; }
        .actions button { border: 0; border-radius: 8px; background: #2563eb; color: white; cursor: pointer; padding: 10px 18px; font-weight: 700; }
        @media print {
            body { background: white; }
            .page { width: auto; min-height: auto; margin: 0; padding: 12mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    {{-- Trang in độc lập giúp trình duyệt chỉ in đúng chứng từ, không kèm modal hoặc menu Filament. --}}
    <main class="page">
        <header class="header">
            <div>
                <p class="eyebrow">{{ config('app.name') }}</p>
                <h1>PHIẾU THU</h1>
            </div>
            <div class="code">
                <strong>{{ 'PT'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                Phiếu xuất: {{ $release->release_code }}<br>
                Ngày thu: {{ $payment->payment_date->format('d/m/Y H:i') }}
            </div>
        </header>

        <section class="meta">
            <div><span>Khách hàng:</span> <strong>{{ $release->customerStock->customer->name }}</strong></div>
            <div><span>Mã Order:</span> <strong>{{ $release->customerStock->order->order_code }}</strong></div>
            <div><span>Điện thoại:</span> {{ $release->customerStock->customer->phone ?: 'Chưa có' }}</div>
            <div><span>Người thu:</span> {{ $payment->confirmer?->name ?? 'Hệ thống' }}</div>
            <div style="grid-column: 1 / -1"><span>Địa chỉ:</span> {{ $release->customerStock->customer->address ?: 'Chưa có' }}</div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sản phẩm</th>
                    <th>SKU</th>
                    <th class="number">Số lượng</th>
                    <th class="number">Đơn giá</th>
                    <th class="number">Tiền sản phẩm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($release->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->customerStockItem->orderItem->productSku->product->name }}</td>
                        <td>{{ $item->customerStockItem->orderItem->productSku->sku_code }}</td>
                        <td class="number">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td class="number">{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</td>
                        <td class="number">{{ number_format((float) $item->amount, 0, ',', '.') }}đ</td>
                    </tr>
                    @foreach ($item->services as $service)
                        <tr>
                            <td></td>
                            <td colspan="2">{{ $service->service_name }}</td>
                            <td class="number">{{ number_format($service->quantity, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format((float) $service->unit_price, 0, ',', '.') }}đ</td>
                            <td class="number">{{ number_format((float) $service->subtotal, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <section class="summary">
            <div class="summary-row"><span>Tiền sản phẩm</span><strong>{{ number_format((float) $release->gross_product_amount, 0, ',', '.') }}đ</strong></div>
            <div class="summary-row"><span>Tiền dịch vụ</span><strong>{{ number_format((float) $release->gross_service_amount, 0, ',', '.') }}đ</strong></div>
            <div class="summary-row"><span>Phí giao hàng</span><strong>{{ number_format((float) $release->allocated_shipping_fee, 0, ',', '.') }}đ</strong></div>
            <div class="summary-row summary-total"><span>Số tiền đã thu</span><strong>{{ number_format((float) $payment->amount, 0, ',', '.') }}đ</strong></div>
        </section>

        <section class="signatures">
            <div><p>Người nộp tiền</p><small>Ký và ghi rõ họ tên</small></div>
            <div><p>Người thu tiền</p><small>Ký và ghi rõ họ tên</small></div>
        </section>
    </main>

    <div class="actions">
        <button type="button" onclick="window.print()">In phiếu thu</button>
    </div>
</body>
</html>
