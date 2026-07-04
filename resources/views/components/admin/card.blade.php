@props([
    'title' => null,
    'icon' => null,
    'hoverable' => false
])

<div {{ $attributes->merge([
    'class' => 'bg-white/90 backdrop-blur-md rounded-2xl border border-gray-200/70 p-6 shadow-sm relative overflow-hidden ' . 
               ($hoverable ? 'hover:shadow-lg hover:-translate-y-1 hover:border-emerald-500/40 transition-all duration-300 ease-out' : 'transition-all duration-300')
]) }}>
    @if($title || isset($header))
        <div class="flex items-center justify-between mb-4">
            @if($title)
                <div class="flex items-center gap-3 relative z-10">
                    @if($icon)
                        <div class="w-10 h-10 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center border border-gray-100 shadow-sm">
                            <span class="material-icons text-[1.125rem]">{{ $icon }}</span>
                        </div>
                    @endif
                    <h3 class="text-sm font-medium text-gray-500 tracking-tight">{{ $title }}</h3>
                </div>
            @endif
            <div class="relative z-10">
                {{ $header ?? '' }}
            </div>
        </div>
    @endif

    <div class="text-gray-900 relative z-10">
        {{ $slot }}
    </div>
</div>
