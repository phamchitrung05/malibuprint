@if ($files !== [])
    {{-- Danh sách này chỉ là state staging; ManagedFile và Attachment được tạo sau khi Order lưu thành công. --}}
    <div class="space-y-2">
        @foreach ($files as $file)
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                    <x-heroicon-o-document class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $file['name'] }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $file['size'] }} · Chờ upload</p>
                </div>

                <button
                    type="button"
                    wire:click="removeStagedAttachment(@js($file['key']))"
                    class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10"
                >
                    Gỡ
                </button>
            </div>
        @endforeach
    </div>
@endif
