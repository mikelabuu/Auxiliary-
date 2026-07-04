@props([
    'title' => null,
    'icon' => null,
    'hoverable' => false
])

<div {{ $attributes->merge([
    'class' => 'bg-white rounded-2xl border border-gray-100 p-6 shadow-sm ' . 
               ($hoverable ? 'hover:shadow-md hover:-translate-y-0.5 transition-all duration-300' : '')
]) }}>
    @if($title || isset($header))
        <div class="flex items-center justify-between mb-4 border-b border-gray-50 pb-3">
            @if($title)
                <div class="flex items-center gap-2">
                    @if($icon)
                        <span class="material-icons text-emerald-600">{{ $icon }}</span>
                    @endif
                    <h3 class="text-lg font-bold text-gray-800">{{ $title }}</h3>
                </div>
            @endif
            {{ $header ?? '' }}
        </div>
    @endif

    <div class="text-gray-600 text-sm">
        {{ $slot }}
    </div>
</div>
