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
                        <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px] text-g-600" />
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
