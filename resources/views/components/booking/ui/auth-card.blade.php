@props([
    'title' => null,
    'subtitle' => null,
])

{{--
    Shared shell for the public auth screens (login/signup, forgot, reset,
    verify). Renders the glass card, logo lockup, and an optional
    title/subtitle. Drop your form (and tabs, if any) into the default slot.
--}}

<div {{ $attributes->merge(['class' => 'bg-white/95 backdrop-blur-md w-full max-w-[500px] rounded-[32px] shadow-[0_40px_80px_-30px_rgba(8,36,20,0.7)] border border-white/60 overflow-hidden']) }}>
    <!-- Logo lockup -->
    <div class="mt-8 flex flex-col items-center gap-2">
        <x-img src="image/FHLogo2.png" alt="Farmers Hostel"
               class="h-20 w-auto drop-shadow-md" sizes="80px" width="80" height="80" />
        <div class="text-center">
            <span class="block text-xl font-semibold text-ink tracking-tight font-display">Farmers <span class="italic text-clsu-700">Hostel</span></span>
            <span class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] mt-0.5">CLSU Campus</span>
        </div>
    </div>

    <div class="px-8 pb-10 pt-6">
        @if($title)
            <div class="text-center mb-6">
                <h2 class="text-2xl font-semibold text-ink leading-tight tracking-tight font-display">{{ $title }}</h2>
                @if($subtitle)
                    <p class="text-sm font-medium text-stone-500 mt-1.5 leading-relaxed">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
