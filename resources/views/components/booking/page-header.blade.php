@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-stone-200/70 pb-5 mb-6']) }}>
    <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-ink tracking-tight font-display">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm font-medium text-stone-500 mt-1.5 leading-relaxed">{{ $subtitle }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
