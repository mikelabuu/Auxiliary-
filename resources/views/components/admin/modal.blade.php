@props([
    'id',
    'icon' => null,
    'color' => 'clsu',
    'title' => null,
    'titleId' => null,
    'maxWidth' => 'md',
    'scrollBody' => false,
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

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-stone-950/50 backdrop-blur-sm" data-modal-close="{{ $id }}"></div>
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
                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" data-modal-close="{{ $id }}" aria-label="Close">
                    <x-admin.icon name="x" class="w-4 h-4" stroke-width="2" />
                </button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
