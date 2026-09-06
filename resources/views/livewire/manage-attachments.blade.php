<div @if ($shouldPoll) wire:poll.3s @endif class="text-left">
    <section>
        {{-- Danh sách gồm cả pending/failed để file staging vẫn có thể tải xuống hoặc retry. --}}
        <div class="space-y-2">
            @forelse ($attachments as $attachment)
                @php($file = $attachment->managedFile)
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-heroicon-o-document class="size-5"/></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $file->original_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $this->formatBytes($file->size) }} · {{ match ($file->status) { 'pending' => 'Chờ upload', 'uploading' => 'Đang upload', 'ready' => 'Đã tải lên Drive', 'failed' => 'Upload thất bại', default => $file->status } }}</p>
                        @if ($file->status === 'failed' && $file->error_message)
                            <p class="mt-1 line-clamp-2 text-xs text-red-600">{{ $file->error_message }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($file->web_view_link)
                            <a href="{{ $file->web_view_link }}" target="_blank" rel="noopener" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600">
                                {{ $file->status === 'ready' ? 'Xem' : 'Tải xuống' }}
                            </a>
                        @endif
                        @if ($file->status === 'failed' && $file->temporary_path)
                            <button type="button" wire:click="retryUpload({{ $file->id }})" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-600">Thử lại</button>
                        @endif
                        <button type="button" wire:click="detachFile({{ $attachment->id }})" wire:confirm="Gỡ liên kết file khỏi đối tượng này?" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Gỡ</button>
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có file đính kèm.</p>
            @endforelse
        </div>
    </section>
</div>
