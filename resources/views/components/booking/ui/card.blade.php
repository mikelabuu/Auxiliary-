@props([
    'title' => null,
    'icon' => null,
    'hoverable' => false,
])

<div {{ $attributes->merge([
    'class' => 'bg-white rounded-3xl border border-stone-200/70 p-6 sm:p-7 shadow-[0_1px_2px_rgba(17,78,40,0.04),0_12px_32px_-16px_rgba(17,78,40,0.14)] ' .
               ($hoverable ? 'hover:shadow-[0_4px_10px_rgba(17,78,40,0.06),0_24px_48px_-18px_rgba(17,78,40,0.2)] hover:-translate-y-0.5 transition-[transform,color,background-color,border-color,box-shadow] duration-300' : '')
]) }}>
    @if($title || isset($header))
        <div class="flex items-center justify-between mb-5 border-b border-stone-100 pb-4">
            @if($title)
                <div class="flex items-center gap-2.5">
                    @if($icon)
                        <span class="w-9 h-9 rounded-xl bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0">
                            <span class="material-icons text-[19px]">{{ $icon }}</span>
                        </span>
                    @endif
                    <h3 class="text-lg font-semibold text-ink tracking-tight font-display">{{ $title }}</h3>
                </div>
            @endif
            {{ $header ?? '' }}
        </div>
    @endif

    <div class="text-stone-600 text-sm">
        {{ $slot }}
    </div>
</div>
