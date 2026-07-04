@props([
    'icon',
    'title',
    'description',
])

<div {{ $attributes->merge(['class' => 'flex items-start gap-5 p-7 rounded-[28px] bg-white border border-slate-200/50 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_20px_40px_rgba(0,0,0,0.08)] hover:border-nautical-teal/20 transition-all duration-300 hover:-translate-y-1.5 group cursor-default']) }}>
    <div class="w-14 h-14 rounded-[18px] bg-slate-50 border border-slate-100 text-nautical-teal flex items-center justify-center flex-shrink-0 group-hover:bg-sky-wash/50 group-hover:scale-105 group-hover:shadow-inner transition-all duration-300">
        <span class="material-icons text-[28px]">{{ $icon }}</span>
    </div>
    <div class="pt-1">
        <h3 class="text-[19px] font-bold text-portrait-ink mb-2 group-hover:text-nautical-teal transition-colors">{{ $title }}</h3>
        <p class="text-[15px] font-medium text-slate-500 leading-relaxed">{{ $description }}</p>
    </div>
</div>
