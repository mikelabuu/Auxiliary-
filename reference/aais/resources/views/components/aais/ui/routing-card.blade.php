@props([
    'label',
    'value',
    'meta' => null,
    'valueXText' => null,
    'metaXText' => null,
    'labelStyle' => 'color:var(--color-au-700);',
    'valueStyle' => null,
    'metaStyle' => 'font-size:12px;margin-top:2px;',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'routing-card']) }}>
    <div class="routing-icon">
        {!! $icon ?: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>' !!}
    </div>

    <div>
        <p class="kv-label" @if($labelStyle) style="{{ $labelStyle }}" @endif>{{ $label }}</p>

        <p class="kv-value" @if($valueStyle) style="{{ $valueStyle }}" @endif @if($valueXText) x-text="{{ $valueXText }}" @endif>
            @if (!$valueXText)
                {{ $value }}
            @endif
        </p>

        @if ($meta || $metaXText)
            <p class="text-muted" @if($metaStyle) style="{{ $metaStyle }}" @endif @if($metaXText) x-text="{{ $metaXText }}" @endif>
                @if (!$metaXText)
                    {{ $meta }}
                @endif
            </p>
        @elseif (trim((string) $slot) !== '')
            {{ $slot }}
        @endif
    </div>
</div>
