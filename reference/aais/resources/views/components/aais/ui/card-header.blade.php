@props([
    'title' => null,
    'icon' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'card-header']) }}>
    <div>
        <h3 class="card-title">
            @if ($icon)
                {!! $icon !!}
            @endif
            {{ $title ?? $slot }}
        </h3>
        @if ($subtitle)
            <p class="card-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="card-header-actions">
            {{ $actions }}
        </div>
    @endif
</div>
