@props([
    'title',
    'subtitle' => null
])

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5 mb-6']) }}>
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm font-medium text-slate-500 mt-1.5 leading-relaxed">{{ $subtitle }}</p>
        @endif
    </div>
    
    @if(isset($actions))
        <div class="flex items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
