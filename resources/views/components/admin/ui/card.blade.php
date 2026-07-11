@props([
    'title' => null,
    'icon' => null,
    'hoverable' => false
])

{{-- Bare AAIS card shell with an optional inline header row. --}}

<div {{ $attributes->merge(['class' => 'card' . ($hoverable ? ' card-hover' : '')]) }}>
    @if($title || isset($header))
        <div class="card-header">
            @if($title)
                <h3 class="card-title">
                    @if($icon)
                        <span class="material-icons" style="font-size:18px;color:var(--color-g-600);">{{ $icon }}</span>
                    @endif
                    {{ $title }}
                </h3>
            @endif
            <div class="card-header-actions">
                {{ $header ?? '' }}
            </div>
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>
</div>
