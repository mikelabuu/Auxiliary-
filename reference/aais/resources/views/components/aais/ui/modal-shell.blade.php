@props([
    'open',
    'closeAction' => null,
    'title' => null,
    'titleXText' => null,
    'size' => null,
    'maxWidth' => null,
    'backdropClass' => null,
    'modalClass' => null,
    'bodyShow' => null,
    'bodyClass' => null,
    'showClose' => true,
])

@php
    $computedBackdropClass = trim('modal-backdrop ' . ($backdropClass ?? ''));
    $computedModalClass = trim('modal ' . ($size === 'lg' ? 'modal-lg ' : '') . ($modalClass ?? ''));
@endphp

<template x-teleport="body">
    <div class="{{ $computedBackdropClass }}" x-show="{{ $open }}" x-transition.opacity x-cloak @if($closeAction) @click.self="{{ $closeAction }}" @endif>
        <div class="{{ $computedModalClass }}" x-show="{{ $open }}" x-transition @if($maxWidth) style="max-width:{{ $maxWidth }};" @endif>
            <div class="modal-header">
                @if (isset($header))
                    {{ $header }}
                @else
                    @if ($titleXText)
                        <h3 class="modal-title" x-text="{{ $titleXText }}"></h3>
                    @else
                        <h3 class="modal-title">{{ $title }}</h3>
                    @endif

                    @if ($showClose && $closeAction)
                        <button class="btn btn-ghost btn-sm btn-icon" @click="{{ $closeAction }}">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                @endif
            </div>

            <div class="modal-body{{ $bodyClass ? ' ' . $bodyClass : '' }}" @if($bodyShow) x-show="{{ $bodyShow }}" @endif>
                {{ $slot }}
            </div>

            @if (isset($footer))
                <div class="modal-footer">{{ $footer }}</div>
            @endif
        </div>
    </div>
</template>
