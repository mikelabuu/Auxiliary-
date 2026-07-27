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
    Modal shell (AAIS .modal): blurred backdrop + centered animated panel with
    an AAIS .modal-header.

    Driven by resources/js/admin-modals.js, which owns open/close, the scroll
    lock, Escape, and the focus trap. Pages still call the same
    openModal('id') / closeModal('id') globals and mark dismiss controls with
    [data-modal-close="id"]; Livewire-owned modals pass `always-visible` +
    `close-action="closeModal"` and the engine animates the exit before
    calling that method.

    <x-admin.ui.modal id="addRoomModal" icon="plus" title="Add New Room">
        <form ...>...fields + footer buttons...</form>
    </x-admin.ui.modal>
--}}

@php
    $maxWidthMap = [
        'sm' => '420px',
        'md' => '520px',
        'lg' => '620px',
        'xl' => '680px',
        '2xl' => '860px',
        // Dossier width: two readable columns side by side (booking detail).
        '3xl' => '1140px',
    ];
    $maxWidthCss = $maxWidthMap[$maxWidth] ?? $maxWidthMap['md'];

    $panelClass = 'modal relative w-full'
        . ($scrollBody ? ' max-h-[90vh] overflow-y-auto custom-scrollbar' : '');

    $resolvedTitleId = $titleId ?: ($title ? $id . '-title' : null);
@endphp

{{-- z-[300]: above the sidebar/topbar (z-50/40) and the dashboard room-map
     popover (z-[210]) so the backdrop dims the whole shell. The engine
     (resources/js/admin-modals.js) raises it further per stack depth.

     `data-modal` is how the engine finds these; `data-modal-close-action`
     marks the Livewire-owned variant, whose close must animate out first and
     then call the component method rather than toggling `hidden` here. --}}
<div id="{{ $id }}" data-modal
     @if($closeAction) data-modal-close-action="{{ $closeAction }}" @endif
     class="{{ $alwaysVisible ? 'flex' : 'hidden' }} fixed inset-0 z-[300] items-center justify-center p-5">
    <div class="modal-backdrop-tint" data-modal-close="{{ $id }}"></div>
    <div {{ $attributes->merge(['class' => $panelClass]) }} style="max-width:{{ $maxWidthCss }};" role="dialog" aria-modal="true" @if($resolvedTitleId) aria-labelledby="{{ $resolvedTitleId }}" @endif tabindex="-1">
        @if($title)
            <div class="modal-header">
                <h3 class="modal-title">
                    @if($icon)
                        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px] shrink-0" stroke-width="2" style="color:var(--color-g-600);" />
                    @endif
                    <span id="{{ $resolvedTitleId }}">{{ $title }}</span>
                </h3>
                <button type="button" class="btn btn-ghost btn-sm btn-icon" data-modal-close="{{ $id }}" aria-label="Close">
                    <x-admin.ui.icon name="x" class="w-4 h-4" stroke-width="2" />
                </button>
            </div>
        @endif
        {{ $slot }}
        @if($scrollBody)
            {{-- GradualBlur (reactbits.dev/animations/gradual-blur): scrollable
                 modal bodies dissolve under a frosted fade pinned to the panel's
                 bottom edge. pointer-events:none so buttons underneath still work. --}}
            <x-admin.ui.gradual-blur mode="sticky" height="4.25rem" :strength="1.6" class="gb-tint-card" />
        @endif
    </div>
</div>
