@php
    $productSubtotal = $order->items->sum(fn ($item): float => (float) $item->subtotal);
    $serviceSubtotal = $order->items->flatMap->services->sum(fn ($service): float => (float) $service->subtotal);
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <x-heroicon-o-user class="size-4"/>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Thông tin khách hàng</h3>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                {{ mb_strtoupper(mb_substr($order->customer?->name ?? 'NB', 0, 2)) }}
            </div>
            <div>
                <p class="font-semibold text-slate-900">{{ $order->customer?->name ?? 'Chưa có khách hàng' }}</p>
                <span
                    class="mt-1 inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600">
                    {{ \App\Support\StatusApp::label('activation.customer', (bool) $order->customer?->is_active) }}
                </span>
            </div>
        </div>

        <div class="mt-5 space-y-3 text-sm text-slate-600">
            <div class="flex items-center gap-3">
                <x-heroicon-o-phone class="size-4 shrink-0 text-slate-400"/>
                {{ $order->customer?->phone ?? 'Chưa có số điện thoại' }}
            </div>
            <div class="flex items-start gap-3">
                <x-heroicon-o-map-pin class="mt-0.5 size-4 shrink-0 text-slate-400"/>
                <span>{{ $order->customer?->address ?? 'Chưa có địa chỉ' }}</span>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <x-heroicon-o-document-text class="size-4"/>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Thông tin đơn hàng</h3>
            </div>
        </div>

        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Mã đơn hàng</dt>
                <dd class="font-semibold text-slate-800">#{{ $order->order_code }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Ngày tạo</dt>
                <dd class="font-medium text-slate-800">{{ $order->order_date?->format('d/m/Y H:i') ?? '--/--' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Trạng thái</dt>
                <dd class="font-medium text-slate-800">{{ \App\Support\StatusApp::label('order.status', $order->status) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Giao hàng</dt>
                <dd class="font-medium {{ $order->is_delivered ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $order->is_delivered ? 'Đã giao' : 'Chưa giao' }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">Thanh toán</dt>
                <dd class="font-medium {{ $order->is_paid ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $order->is_paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                </dd>
            </div>
        </dl>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <x-heroicon-o-banknotes class="size-4"/>
            </div>
            <h3 class="text-sm font-bold text-slate-900">Tổng tiền</h3>
        </div>

        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Tiền sản phẩm</dt>
                <dd class="font-medium text-slate-800">{{ number_format($productSubtotal, 0, ',', '.') }}đ</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Tiền dịch vụ</dt>
                <dd class="font-medium text-slate-800">{{ number_format($serviceSubtotal, 0, ',', '.') }}đ</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Giảm giá</dt>
                <dd class="font-medium text-slate-800">{{ number_format((float) $order->discount, 0, ',', '.') }}đ</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500">Phí vận chuyển</dt>
                <dd class="font-medium text-slate-800">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}đ</dd>
            </div>
        </dl>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
            <span class="font-bold text-slate-900">Tổng cộng</span>
            <span class="text-xl font-bold text-blue-600">{{ number_format((float) $order->total_amount, 0, ',', '.') }}đ</span>
        </div>
    </section>
</div>
