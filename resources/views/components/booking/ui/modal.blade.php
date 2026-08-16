@props([
    'id',
    'title' => '',
    'size' => 'md',
])

@php
    $sizes = [
        'sm'  => 'max-w-md',
        'md'  => 'max-w-lg',
        'lg'  => 'max-w-2xl',
        'xl'  => 'max-w-4xl',
        '2xl' => 'max-w-6xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    $titleId = $title ? $id . '-title' : null;
@endphp

{{-- Driven by resources/js/admin-modals.js — the same engine the staff console
     uses, which app.js already ships to every public page. It owns open/close,
     the scroll lock, Escape, the focus trap and focus restore; this markup just
     has to honour the contract:

       [data-modal]          the root the engine tracks
       [data-modal-backdrop] the click-to-dismiss layer (press *and* release
                             must land on it, so a text-selection drag out of
                             the panel doesn't close the dialog)
       [data-modal-close]    any dismiss control
       .pub-modal-panel      the animated panel the exit transition waits on

     Open with window.openModal('id'), not classList.remove('hidden') — the
     latter skips the lock and the focus trap entirely. --}}
<div id="{{ $id }}" data-modal
     class="pub-modal fixed inset-0 z-[70] hidden items-center justify-center p-4"
>
    {{-- The tint is its own layer rather than a background on the root: the
         engine needs a discrete element to test backdrop clicks against. --}}
    <div class="absolute inset-0 bg-clsu-950/50 backdrop-blur-sm"
         data-modal-backdrop data-modal-close="{{ $id }}"></div>

    <!-- Modal Container -->
    <div class="pub-modal-panel relative bg-white rounded-3xl shadow-2xl w-full {{ $sizeClass }} overflow-hidden flex flex-col border border-stone-100 max-h-[90vh] animate-pop"
         role="dialog"
         aria-modal="true"
         @if($titleId) aria-labelledby="{{ $titleId }}" @endif
         tabindex="-1"
    >

        <!-- Header -->
        <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between">
            <h3 @if($titleId) id="{{ $titleId }}" @endif class="text-lg font-semibold text-ink tracking-tight font-display">{{ $title }}</h3>
            <button type="button"
                    class="text-stone-400 hover:text-stone-600 w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center transition-colors cursor-pointer"
                    data-modal-close="{{ $id }}"
                    aria-label="Close"
            >
                <x-booking.ui.icon-solid name="xmark" class="text-[20px]" />
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 overflow-y-auto flex-1 text-stone-600 text-sm custom-scrollbar">
            {{ $slot }}
        </div>

        <!-- Footer -->
        @if(isset($footer))
            <div class="px-6 py-4 border-t border-stone-100 bg-stone-50/60 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
