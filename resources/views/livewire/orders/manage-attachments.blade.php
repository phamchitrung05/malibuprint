<div x-data="{ detachAttachmentId: null }" @if ($shouldPoll) wire:poll.3s @endif class="text-left">
    <section>
        {{-- Danh sách gồm cả pending/failed để file staging vẫn có thể tải xuống hoặc retry. --}}
        <div class="space-y-2">
            @forelse ($attachments as $attachment)
                @php($file = $attachment->managedFile)
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-heroicon-o-document class="size-5"/></div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $file->original_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $this->formatBytes($file->size) }} · {{ \App\Support\StatusApp::label('managed_file.status', $file->status, $file->status) }}</p>
                        @if ($file->status === \App\Support\StatusApp::value('managed_file.status', 'failed') && $file->error_message)
                            <p class="mt-1 line-clamp-2 text-xs text-red-600">{{ $file->error_message }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($file->web_view_link)
                            <a href="{{ $file->web_view_link }}" target="_blank" rel="noopener" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600">
                                {{ $file->status === \App\Support\StatusApp::value('managed_file.status', 'ready') ? 'Xem' : 'Tải xuống' }}
                            </a>
                        @endif
                        @if ($file->web_content_link)
                            <a href="{{ $file->web_content_link }}" title="Tải xuống" aria-label="Tải xuống" class="flex size-8 items-center justify-center rounded-lg border border-blue-200 text-blue-600 transition hover:bg-blue-50">
                                <x-heroicon-o-arrow-down-tray class="size-4" />
                            </a>
                        @endif
                        @if ($file->status === \App\Support\StatusApp::value('managed_file.status', 'failed') && $file->temporary_path)
                            <button type="button" wire:click="retryUpload({{ $file->id }})" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-600">Thử lại</button>
                        @endif
                        <button type="button" x-on:click="detachAttachmentId = {{ $attachment->id }}" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Gỡ</button>
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có file đính kèm.</p>
            @endforelse
        </div>
    </section>

    <x-ui.confirmation-modal
        state="detachAttachmentId !== null"
        close="detachAttachmentId = null"
        confirm-action="$wire.detachFile(detachAttachmentId)"
        title="Xác nhận gỡ file"
        description="Liên kết file với đối tượng hiện tại sẽ bị gỡ. File gốc vẫn được giữ lại trong hệ thống."
        confirm-label="Gỡ liên kết"
        cancel-label="Giữ file"
        wire-target="detachFile"
    />
</div>
