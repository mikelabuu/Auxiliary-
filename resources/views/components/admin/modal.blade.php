@props([
    'id',
    'icon' => null,
    'color' => 'clsu',
    'title' => null,
    'titleId' => null,
    'maxWidth' => 'md',
    'scrollBody' => false,
    'alwaysVisible' => false,
    'closeAction' => null,
])

{{--
    Reusable modal shell: backdrop + centered panel + optional icon/title/close
    header. Works with the existing jQuery show/hide pattern already used across
    the admin panel (`openModal('id')` / `closeModal('id')` just toggle
    hidden/flex, and `[data-modal-close]` buttons close by id) — no JS changes
    needed to adopt it.

    <x-admin.modal id="addRoomModal" icon="plus" title="Add New Room">
        <form ...>...fields + footer buttons...</form>
    </x-admin.modal>

    Pass `title-id` when a page needs to swap the header text via JS
    (e.g. "Add Room Type" <-> "Edit Room Type").

    For a Livewire component that already conditionally renders this modal
    (e.g. `@if($selectedBooking) <x-admin.modal ...>` — Livewire's own `@if`
    is the show/hide mechanism, not a JS class toggle), pass `always-visible`
    so the panel isn't born `hidden`, and `close-action="closeModal"` so the
    backdrop and header-X button emit `wire:click="closeModal"` instead of
    the jQuery-oriented `data-modal-close` attribute.

    <x-admin.modal id="bookingDetailModal" title="Booking Details" always-visible close-action="closeModal">
        ...
    </x-admin.modal>
--}}

@php
    $colorMap = config('adminui.chip_colors');
    $chipColor = $colorMap[$color] ?? $colorMap['clsu'];

    $maxWidthMap = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];
    $maxWidthClass = $maxWidthMap[$maxWidth] ?? $maxWidthMap['md'];

    $panelClass = 'relative bg-white rounded-2xl shadow-card-lg w-full ' . $maxWidthClass . ' overflow-hidden animate-pop'
        . ($scrollBody ? ' max-h-[90vh] overflow-y-auto' : '');

    $resolvedTitleId = $titleId ?: ($title ? $id . '-title' : null);
@endphp

<div id="{{ $id }}" class="{{ $alwaysVisible ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-stone-950/50 backdrop-blur-sm" @if($closeAction) wire:click="{{ $closeAction }}" @else data-modal-close="{{ $id }}" @endif></div>
    <div {{ $attributes->merge(['class' => $panelClass]) }} role="dialog" aria-modal="true" @if($resolvedTitleId) aria-labelledby="{{ $resolvedTitleId }}" @endif tabindex="-1">
        @if($title)
            <div class="flex items-center justify-between border-b border-stone-100 bg-stone-50/50 px-6 py-4">
                <h3 class="font-bold text-stone-900 text-base flex items-center gap-2.5">
                    @if($icon)
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $chipColor }}">
                            <x-admin.icon :name="$icon" class="w-4 h-4" stroke-width="2" />
                        </span>
                    @endif
                    <span id="{{ $resolvedTitleId }}">{{ $title }}</span>
                </h3>
                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" @if($closeAction) wire:click="{{ $closeAction }}" @else data-modal-close="{{ $id }}" @endif aria-label="Close">
                    <x-admin.icon name="x" class="w-4 h-4" stroke-width="2" />
                </button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
