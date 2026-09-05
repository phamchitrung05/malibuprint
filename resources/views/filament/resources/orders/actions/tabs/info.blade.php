<div class="grid gap-3 lg:grid-cols-2">
    <section class="rounded-2xl border border border-slate-100 shadow-sm">
        <h3 class="pb-2 p-3 rounded-t-2xl bg-cyan-100 border-b border-slate-50 text-lg font-extrabold">Thông tin
            khách hàng</h3>
       <div class="p-5">
           <div class="flex items-center gap-3">
               <div
                   class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-xl font-extrabold text-blue-600">{{ mb_strtoupper(mb_substr($order->customer?->name ?? 'NB', 0, 2)) }}</div>
               <div><p class="font-extrabold">{{ $order->customer?->name ?? 'Chưa có khách hàng' }}</p>
                   <p class="text-sm text-slate-500">Công ty XYZ</p></div>
           </div>
           <div class="mt-5 grid gap-3 text-sm">
               <p>
                   <x-heroicon-o-phone
                       class="mr-2 inline h-5 w-5"/>{{ $order->customer?->phone ?? 'Chưa có số điện thoại' }}</p>
               <p>
                   <x-heroicon-o-map-pin class="mr-2 inline h-5 w-5"/>{{ $order->customer?->address ?? 'Chưa có địa chỉ' }}
               </p>
           </div>
       </div>
    </section>
    <section class="rounded-2xl border border border-slate-100 bg-white shadow-sm">
        <h3
            class="pb-2 p-3 rounded-t-2xl bg-sky-100 border-b border-slate-50 text-lg font-extrabold">
            Thông tin
            đơn hàng
        </h3>
        <dl class="mt-4 grid gap-4 text-sm p-5">
            <div class="flex justify-between">
                <dt class="text-slate-500">Mã đơn</dt>
                <dd><b>#{{ $order->order_code }}</b></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Ngày tạo</dt>
                <b><b></b>{{ $order->order_date?->format('d/m/Y H:i') }}</b></dd>
            </div>
        </dl>
    </section>
</div>
