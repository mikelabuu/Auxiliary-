@props([
    'icon',
    'title',
    'description',
])

<div {{ $attributes->merge(['class' => 'flex items-start gap-5 p-7 rounded-[28px] bg-white border border-stone-200/60 shadow-[0_4px_20px_-8px_rgba(17,78,40,0.08)] hover:shadow-[0_24px_44px_-20px_rgba(17,78,40,0.22)] hover:border-clsu-200 transition-all duration-300 hover:-translate-y-1.5 group cursor-default']) }}>
    <div class="w-14 h-14 rounded-[18px] bg-clsu-50 border border-clsu-100 text-clsu-700 flex items-center justify-center flex-shrink-0 group-hover:bg-clsu-100 group-hover:scale-105 transition-all duration-300">
        <span class="material-icons text-[28px]">{{ $icon }}</span>
    </div>
    <div class="pt-1">
        <h3 class="text-xl font-semibold text-ink mb-2 tracking-tight font-display group-hover:text-clsu-800 transition-colors">{{ $title }}</h3>
        <p class="text-[15px] font-medium text-stone-500 leading-relaxed">{{ $description }}</p>
    </div>
</div>
