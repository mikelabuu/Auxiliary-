@props([
    'icon',
    'value',
    'label',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10']) }}>
    <div class="w-10 h-10 rounded-xl bg-white/10 text-accent flex items-center justify-center flex-shrink-0">
        <span class="material-icons text-[20px]">{{ $icon }}</span>
    </div>
    <div class="text-left leading-tight">
        <span class="block text-sm font-black text-white">{{ $value }}</span>
        <span class="block text-[11px] font-semibold text-slate-300">{{ $label }}</span>
    </div>
</div>
