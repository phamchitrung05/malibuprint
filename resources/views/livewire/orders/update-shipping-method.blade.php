<section data-shipping-method-section class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div class="flex min-w-0 items-center gap-2">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <x-heroicon-o-qr-code class="size-4" />
            </div>
            <h3 class="truncate text-sm font-bold text-slate-900">Mã vận đơn</h3>
        </div>
        @if ($isBestExpress)
            <span class="shrink-0 rounded-md bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-600">Best Express</span>
        @endif
    </div>

    <div class="p-5">
        <div class="min-w-0">
            <label for="shipping-tracking-code-{{ $orderId }}" class="block text-xs font-medium text-slate-600">
                Mã vận đơn Best Express
            </label>
            <x-filament::input.wrapper
                :disabled="! $canUpdate"
                :valid="! $errors->has('trackingCode')"
                class="mt-2 w-full"
            >
                <div class="flex min-w-0 items-stretch">
                    <x-filament::input
                        id="shipping-tracking-code-{{ $orderId }}"
                        type="text"
                        maxlength="100"
                        wire:model="trackingCode"
                        :disabled="! $canUpdate"
                        placeholder="Nhập mã vận đơn"
                        class="min-w-0 flex-1"
                    />
                    @if ($canUpdate)
                        <button
                            type="button"
                            data-tracking-code-suffix
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-e-lg border-s border-primary-500 bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <x-heroicon-o-check class="size-4" />
                            <span wire:loading.remove wire:target="save">Lưu</span>
                            <span wire:loading wire:target="save">Đang lưu...</span>
                        </button>
                    @endif
                </div>
            </x-filament::input.wrapper>
            <p class="mt-2 text-xs text-slate-500">
                Có mã vận đơn là Best Express; để trống và lưu để dùng giao hàng thông thường.
            </p>
            @error('trackingCode')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @error('shippingMethod')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @unless ($canUpdate)
        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
            {{ $isDelivered
                ? 'Mã vận đơn đã được khóa sau khi xác nhận giao hàng.'
                : 'Có thể cập nhật mã vận đơn sau khi đơn hoàn thành sản xuất.' }}
        </p>
    @endunless
</section>
