<section class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-5 flex items-center gap-2">
        <div class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-heroicon-o-clock class="size-4"/></div>
        <h3 class="text-sm font-bold text-slate-900">Lịch sử đơn hàng</h3>
    </div>

    @if ($order->activities->isEmpty())
        <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Chưa có hoạt động nào được ghi nhận.</p>
    @else
        <div class="space-y-4 border-l-2 border-slate-200 pl-5 text-sm">
            @foreach ($order->activities as $activity)
                <div class="relative rounded-lg bg-slate-50 p-4 before:absolute before:-left-[1.72rem] before:top-5 before:size-3 before:rounded-full before:bg-blue-500 before:ring-4 before:ring-white">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="font-semibold text-slate-800">{{ $activity->description }}</p>
                        <time class="text-xs text-slate-400">{{ $activity->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Thực hiện bởi {{ $activity->causer?->name ?? 'Hệ thống' }}
                    </p>
                    @if ($activity->getExtraProperty('reason'))
                        <p class="mt-2 rounded bg-red-50 px-2 py-1.5 text-xs text-red-700">
                            Lý do: {{ $activity->getExtraProperty('reason') }}
                        </p>
                    @endif
                    @if ($activity->getExtraProperty('amount'))
                        <p class="mt-2 text-xs font-medium text-slate-600">
                            Số tiền: {{ number_format((float) $activity->getExtraProperty('amount'), 0, ',', '.') }}đ
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
