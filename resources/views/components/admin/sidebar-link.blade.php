@props([
    'href',
    'active' => false,
    'badge' => null
])

<a href="{{ $href }}" 
   {{ $attributes->merge([
       'class' => 'group relative flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-clsu-950 !no-underline ' .
                  ($active ? 'bg-[#247c16] text-white font-semibold border border-palay-400/35 shadow-[0_4px_12px_rgba(0,0,0,0.15)]' : 'text-clsu-200/85 hover:bg-white/5 hover:text-white')
   ]) }}>
    @if ($active)
        <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-1.5 h-8 bg-palay-400 rounded-r-md"></span>
    @endif
    
    {{ $icon }}
    
    <span class="label-fade truncate flex-1">{{ $slot }}</span>

    @if ($badge)
        <span class="label-fade bg-palay-500 text-white text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center shrink-0 shadow-sm">{{ $badge }}</span>
    @endif
</a>
