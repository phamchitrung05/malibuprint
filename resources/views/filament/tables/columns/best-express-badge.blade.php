@if ($getRecord()->shipping_method === \App\Enums\ShippingMethod::BestExpress)
    <span
        title="Giao bằng Best Express"
        class="inline-flex overflow-hidden rounded-md text-[10px] font-black leading-5 tracking-tight shadow-sm ring-1 ring-slate-200"
    >
        <span class="bg-[#e31e24] px-1.5 text-white">BEST</span>
        <span class="bg-[#164194] px-1.5 text-white">EXPRESS</span>
    </span>
@endif
