<section class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600"><x-heroicon-o-paper-clip class="size-4"/></div>
            <h3 class="text-sm font-bold text-slate-900">Tệp đính kèm</h3>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100">
            <x-heroicon-o-arrow-up-tray class="size-4"/>Tải tệp lên
        </button>
    </div>
    {{-- Chưa có bảng tệp đính kèm nên giữ dữ liệu thiết kế mẫu của giao diện mới. --}}
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([['name' => 'card_visit_final.pdf', 'size' => '2.4 MB'], ['name' => 'card_visit_preview.pdf', 'size' => '1.1 MB']] as $file)
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                <div class="flex size-10 items-center justify-center rounded-lg bg-red-50 text-red-500"><x-heroicon-o-document class="size-5"/></div>
                <div class="min-w-0"><b class="block truncate text-sm text-slate-800">{{ $file['name'] }}</b><small class="text-slate-500">{{ $file['size'] }} · Đã tải lên</small></div>
            </div>
        @endforeach
    </div>
</section>
