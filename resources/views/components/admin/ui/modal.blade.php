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
    an AAIS .modal-header. Mechanics unchanged — works with the existing jQuery
    openModal('id') / closeModal('id') hidden/flex toggle and
    [data-modal-close] buttons, or Livewire via `always-visible` +
    `close-action="closeModal"`.

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
    ];
    $maxWidthCss = $maxWidthMap[$maxWidth] ?? $maxWidthMap['md'];

    $panelClass = 'modal relative w-full'
        . ($scrollBody ? ' max-h-[90vh] overflow-y-auto custom-scrollbar' : '');

    $resolvedTitleId = $titleId ?: ($title ? $id . '-title' : null);
@endphp

<div id="{{ $id }}" class="{{ $alwaysVisible ? 'flex' : 'hidden' }} fixed inset-0 z-[200] items-center justify-center p-5">
    <div class="modal-backdrop-tint" @if($closeAction) wire:click="{{ $closeAction }}" @else data-modal-close="{{ $id }}" @endif></div>
    <div {{ $attributes->merge(['class' => $panelClass]) }} style="max-width:{{ $maxWidthCss }};" role="dialog" aria-modal="true" @if($resolvedTitleId) aria-labelledby="{{ $resolvedTitleId }}" @endif tabindex="-1">
        @if($title)
            <div class="modal-header">
                <h3 class="modal-title">
                    @if($icon)
                        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px] shrink-0" stroke-width="2" style="color:var(--color-g-600);" />
                    @endif
                    <span id="{{ $resolvedTitleId }}">{{ $title }}</span>
                </h3>
                <button type="button" class="btn btn-ghost btn-sm btn-icon" @if($closeAction) wire:click="{{ $closeAction }}" @else data-modal-close="{{ $id }}" @endif aria-label="Close">
                    <x-admin.ui.icon name="x" class="w-4 h-4" stroke-width="2" />
                </button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
