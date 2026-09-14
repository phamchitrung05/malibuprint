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
            <span>Phương thức vận chuyển</span>
            <x-filament::input.wrapper :disabled="! $canUpdate" :valid="! $errors->has('shippingMethod')">
                <x-filament::input.select wire:model.live="shippingMethod" :disabled="! $canUpdate">
                @foreach (\App\Enums\ShippingMethod::cases() as $method)
                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            @error('shippingMethod') <span class="text-red-600">{{ $message }}</span> @enderror
        </label>

        @if ($shippingMethod === \App\Enums\ShippingMethod::Express->value)
            <label class="grid gap-2 text-xs font-medium text-slate-600 md:col-span-2">
                Mã giao hàng nhanh
                <input
                    id="shipping-tracking-code-{{ $orderId }}"
                    wire:model="trackingCode"
                    type="text"
                    maxlength="100"
                    @disabled(! $canUpdate)
                    class="rounded-lg border-slate-300 text-sm"
                    placeholder="Nhập mã giao hàng nhanh"
                />
                @error('trackingCode') <span class="text-red-600">{{ $message }}</span> @enderror
            </label>
        @elseif ($shippingMethod === \App\Enums\ShippingMethod::Vehicle->value)
            <label class="grid gap-2 text-xs font-medium text-slate-600 md:col-span-2">
                <span>Tài xế</span>
                <x-filament::input.wrapper :disabled="! $canUpdate" :valid="! $errors->has('driverId')">
                    <x-filament::input.select wire:model="driverId" :disabled="! $canUpdate">
                    <option value="">Chọn tài xế</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}">
                            {{ $driver->name }} - {{ $driver->phone }}{{ filled($driver->license_plate) ? ' - '.$driver->license_plate : '' }}
                        </option>
                    @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @error('driverId') <span class="text-red-600">{{ $message }}</span> @enderror
            </label>
        @elseif ($shippingMethod === \App\Enums\ShippingMethod::CustomerPickup->value)
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 md:col-span-2">
                Khách hàng sẽ tự tới lấy hàng. Không cần nhập mã giao hàng hoặc chọn tài xế.
            </div>
        @else
            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700 md:col-span-2">
                Đơn hàng sẽ được giao trong khu vực nội thành. Không cần nhập mã giao hàng hoặc chọn tài xế.
            </div>
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
