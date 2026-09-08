@props([
    'state',
    'close',
    'confirmAction',
    'title',
    'description' => null,
    'confirmLabel' => 'Xác nhận',
    'cancelLabel' => 'Hủy',
    'tone' => 'danger',
    'wireTarget' => null,
])

@php
    $styles = match ($tone) {
        'success' => [
            'icon' => 'bg-emerald-50 text-emerald-600',
            'button' => 'bg-emerald-600 hover:bg-emerald-700',
            'component' => 'heroicon-o-check-circle',
        ],
        'warning' => [
            'icon' => 'bg-amber-50 text-amber-600',
            'button' => 'bg-amber-500 hover:bg-amber-600',
            'component' => 'heroicon-o-exclamation-circle',
        ],
        default => [
            'icon' => 'bg-red-50 text-red-600',
            'button' => 'bg-red-600 hover:bg-red-700',
            'component' => 'heroicon-o-exclamation-triangle',
        ],
    };
    $dialogId = 'confirmation-modal-'.substr(md5($state.$title), 0, 10);
@endphp

<template x-teleport="body">
    <div
        x-cloak
        x-show="{{ $state }}"
        x-on:keydown.escape.window="{{ $close }}"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $dialogId }}-title"
        @if (filled($description)) aria-describedby="{{ $dialogId }}-description" @endif
    >
        <div
            x-show="{{ $state }}"
            x-on:click.outside="{{ $close }}"
            x-transition
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div class="flex size-12 items-center justify-center rounded-full {{ $styles['icon'] }}">
                <x-dynamic-component :component="$styles['component']" class="size-7" />
            </div>

            <h2 id="{{ $dialogId }}-title" class="mt-4 text-lg font-bold text-slate-900">{{ $title }}</h2>

            @if (filled($description))
                <p id="{{ $dialogId }}-description" class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif

            {{ $slot }}

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    x-on:click="{{ $close }}"
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    x-on:click="Promise.resolve({{ $confirmAction }}).then(() => { {{ $close }} })"
                    @if ($wireTarget) wire:loading.attr="disabled" wire:target="{{ $wireTarget }}" @endif
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-wait disabled:opacity-60 {{ $styles['button'] }}"
                >
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
