<section data-shipping-method-section class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div class="flex min-w-0 items-center gap-2">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <x-heroicon-o-truck class="size-4" />
            </div>
            <h3 class="truncate text-sm font-bold text-slate-900">Phương thức vận chuyển</h3>
        </div>
        <span class="shrink-0 rounded-md bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-600">
            {{ \App\Support\StatusApp::label('order.shipping_method', $shippingMethod) }}
        </span>
    </div>

    <div class="grid gap-4 p-5 md:grid-cols-2">
        <label class="grid gap-2 text-xs font-medium text-slate-600">
            Phương thức vận chuyển
            <select wire:model.live="shippingMethod" @disabled(! $canUpdate) class="rounded-lg border-slate-300 text-sm">
                @foreach (\App\Enums\ShippingMethod::cases() as $method)
                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-xs font-medium text-slate-600">
            Đơn vị vận chuyển
            <select wire:model="shippingProviderId" @disabled(! $canUpdate) class="rounded-lg border-slate-300 text-sm">
                <option value="">Chọn đơn vị vận chuyển</option>
                @foreach ($shippingProviders as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                @endforeach
            </select>
            @error('shippingProviderId') <span class="text-red-600">{{ $message }}</span> @enderror
        </label>

        @if ($shippingMethod === \App\Enums\ShippingMethod::Express->value)
            <label class="grid gap-2 text-xs font-medium text-slate-600 md:col-span-2">
                Mã vận đơn
                <input
                    id="shipping-tracking-code-{{ $orderId }}"
                    wire:model="trackingCode"
                    type="text"
                    maxlength="100"
                    @disabled(! $canUpdate)
                    class="rounded-lg border-slate-300 text-sm"
                    placeholder="Nhập mã vận đơn"
                />
                @error('trackingCode') <span class="text-red-600">{{ $message }}</span> @enderror
            </label>
        @else
            <label class="grid gap-2 text-xs font-medium text-slate-600 md:col-span-2">
                Tài xế
                <select wire:model="driverId" @disabled(! $canUpdate) class="rounded-lg border-slate-300 text-sm">
                    <option value="">Chọn tài xế</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}">
                            {{ $driver->name }} - {{ $driver->phone }}{{ filled($driver->license_plate) ? ' - '.$driver->license_plate : '' }}
                        </option>
                    @endforeach
                </select>
                @error('driverId') <span class="text-red-600">{{ $message }}</span> @enderror
            </label>
        @endif

        @error('shippingMethod') <p class="text-xs text-red-600 md:col-span-2">{{ $message }}</p> @enderror

        @if ($canUpdate)
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-70 md:col-span-2"
            >
                <x-heroicon-o-check class="size-4" />
                <span wire:loading.remove wire:target="save">Lưu thông tin vận chuyển</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        @endif
    </div>

    @unless ($canUpdate)
        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
            {{ $isDelivered
                ? 'Thông tin vận chuyển đã được khóa sau khi xác nhận giao hàng.'
                : 'Có thể cập nhật vận chuyển sau khi đơn hoàn thành sản xuất.' }}
        </p>
    @endunless
</section>
